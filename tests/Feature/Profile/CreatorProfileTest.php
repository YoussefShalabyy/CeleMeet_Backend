<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\Category;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorProfileTest extends TestCase
{
    use RefreshDatabase;

    // ─── GET /creators ────────────────────────────────────────────────────────

    public function test_list_creators_is_public(): void
    {
        $this->getJson('/api/v1/creators')->assertStatus(200);
    }

    public function test_list_creators_returns_paginated_results(): void
    {
        $celebrity = User::factory()->celebrity()->create();
        CreatorProfile::create([
            'user_id'      => $celebrity->id,
            'display_name' => 'Test Creator',
            'is_active'    => true,
        ]);

        $response = $this->getJson('/api/v1/creators');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data', 'meta' => ['total', 'per_page', 'current_page']]);
    }

    // ─── GET /creators/{id} ───────────────────────────────────────────────────

    public function test_get_creator_profile_returns_404_for_nonexistent(): void
    {
        $this->getJson('/api/v1/creators/99999')->assertStatus(404);
    }

    public function test_get_creator_profile_returns_profile(): void
    {
        $celebrity = User::factory()->celebrity()->create(['username' => 'bigstar']);
        CreatorProfile::create([
            'user_id'      => $celebrity->id,
            'display_name' => 'Big Star',
            'is_active'    => true,
        ]);

        $this->getJson("/api/v1/creators/{$celebrity->id}")
            ->assertStatus(200)
            ->assertJson(['data' => ['display_name' => 'Big Star']]);
    }

    // ─── PUT /creator/profile ─────────────────────────────────────────────────

    public function test_update_creator_profile_requires_auth(): void
    {
        $this->putJson('/api/v1/creator/profile', ['display_name' => 'Test'])->assertStatus(401);
    }

    public function test_regular_user_cannot_update_creator_profile(): void
    {
        $user = User::factory()->create(); // role = 'regular'
        $token = auth('api')->login($user);

        $this->withToken($token)
            ->putJson('/api/v1/creator/profile', ['display_name' => 'Test'])
            ->assertStatus(403);
    }

    public function test_celebrity_can_update_creator_profile(): void
    {
        $celebrity = User::factory()->celebrity()->create();
        $token = auth('api')->login($celebrity);

        $response = $this->withToken($token)
            ->putJson('/api/v1/creator/profile', [
                'display_name' => 'Celebrity Name',
                'bio'          => 'My bio',
            ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['display_name' => 'Celebrity Name']]);

        $this->assertDatabaseHas('creator_profiles', [
            'user_id'      => $celebrity->id,
            'display_name' => 'Celebrity Name',
        ]);
    }

    public function test_celebrity_can_set_categories(): void
    {
        $celebrity = User::factory()->celebrity()->create();
        $token = auth('api')->login($celebrity);

        $cat1 = Category::create(['name' => 'Music', 'slug' => 'music', 'sort_order' => 1]);
        $cat2 = Category::create(['name' => 'Sport', 'slug' => 'sport', 'sort_order' => 2]);

        $this->withToken($token)
            ->putJson('/api/v1/creator/profile', [
                'display_name' => 'Music Star',
                'category_ids' => [$cat1->id, $cat2->id],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('creator_categories', ['creator_id' => $celebrity->id, 'category_id' => $cat1->id]);
        $this->assertDatabaseHas('creator_categories', ['creator_id' => $celebrity->id, 'category_id' => $cat2->id]);
    }

    // ─── GET /categories ──────────────────────────────────────────────────────

    public function test_list_categories_is_public(): void
    {
        Category::create(['name' => 'Acting', 'slug' => 'acting', 'sort_order' => 1]);

        $this->getJson('/api/v1/categories')
            ->assertStatus(200)
            ->assertJson(['data' => [['name' => 'Acting']]]);
    }
}

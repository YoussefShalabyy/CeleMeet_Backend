<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\Category;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    // ─── GET /users/me ────────────────────────────────────────────────────────

    public function test_get_own_profile_requires_auth(): void
    {
        $this->getJson('/api/v1/users/me')->assertStatus(401);
    }

    public function test_get_own_profile_returns_user_data(): void
    {
        $user = User::factory()->create(['email' => 'profile@example.com']);
        $token = auth('api')->login($user);

        $this->withToken($token)
            ->getJson('/api/v1/users/me')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => ['email' => 'profile@example.com', 'role' => 'regular'],
            ]);
    }

    // ─── PUT /users/me ────────────────────────────────────────────────────────

    public function test_update_own_profile_requires_auth(): void
    {
        $this->putJson('/api/v1/users/me', ['username' => 'newname'])->assertStatus(401);
    }

    public function test_update_own_profile_changes_username(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)
            ->putJson('/api/v1/users/me', ['username' => 'newusername'])
            ->assertStatus(200)
            ->assertJson(['data' => ['username' => 'newusername']]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'newusername']);
    }

    public function test_update_profile_with_duplicate_username_returns_422(): void
    {
        User::factory()->create(['username' => 'taken']);
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)
            ->putJson('/api/v1/users/me', ['username' => 'taken'])
            ->assertStatus(422);
    }
}

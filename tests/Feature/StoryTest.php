<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CreatorProfile;
use App\Models\Follow;
use App\Models\MediaAsset;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class StoryTest extends TestCase
{
    use RefreshDatabase;

    private function createCreator(): User
    {
        $creator = User::factory()->create(['role' => 'celebrity']);
        CreatorProfile::create([
            'user_id'      => $creator->id,
            'display_name' => 'Test Creator',
        ]);
        return $creator;
    }

    private function createMedia(User $owner): MediaAsset
    {
        return MediaAsset::create([
            'owner_id'   => $owner->id,
            'owner_type' => User::class,
            'collection' => 'story',
            'provider'   => 'cloudinary',
            'url'        => 'http://example.com/img.jpg',
        ]);
    }

    public function test_creator_can_create_a_story(): void
    {
        $creator = $this->createCreator();
        $media   = $this->createMedia($creator);
        $token   = auth('api')->login($creator);

        $response = $this->withToken($token)->postJson('/api/v1/stories', [
            'media_id'   => $media->id,
            'is_premium' => false,
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        
        $this->assertDatabaseHas('stories', [
            'creator_id' => $creator->id,
            'media_id'   => $media->id,
            'is_premium' => 0, // boolean false
        ]);

        $story = Story::first();
        // Check expiration is 24 hours in the future
        $this->assertTrue(now()->addHours(24)->isSameAs('Y-m-d H:i:s', $story->expires_at));

        // Media ownership morphed
        $this->assertDatabaseHas('media_assets', [
            'id'         => $media->id,
            'owner_type' => Story::class,
            'owner_id'   => $story->id,
        ]);
    }

    public function test_regular_user_cannot_create_a_story(): void
    {
        $user  = User::factory()->create(['role' => 'regular']);
        $media = $this->createMedia($user);
        $token = auth('api')->login($user);

        $this->withToken($token)->postJson('/api/v1/stories', [
            'media_id'   => $media->id,
            'is_premium' => false,
        ])->assertStatus(422); // Business exception, not a creator
    }

    public function test_creator_cannot_use_others_media_for_story(): void
    {
        $creator = $this->createCreator();
        $other   = User::factory()->create();
        $media   = $this->createMedia($other);
        $token   = auth('api')->login($creator);

        $this->withToken($token)->postJson('/api/v1/stories', [
            'media_id'   => $media->id,
            'is_premium' => false,
        ])->assertStatus(422);
    }

    public function test_feed_only_shows_active_stories_from_followed_creators(): void
    {
        $creator1 = $this->createCreator();
        $creator2 = $this->createCreator();
        
        Story::factory()->create(['creator_id' => $creator1->id, 'media_id' => $this->createMedia($creator1)->id, 'expires_at' => now()->addHours(1)]);
        // Expired story
        Story::factory()->create(['creator_id' => $creator1->id, 'media_id' => $this->createMedia($creator1)->id, 'expires_at' => now()->subHours(1)]);
        // Unfollowed creator's story
        Story::factory()->create(['creator_id' => $creator2->id, 'media_id' => $this->createMedia($creator2)->id, 'expires_at' => now()->addHours(1)]);

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        Follow::create(['follower_id' => $user->id, 'creator_id' => $creator1->id]);

        $response = $this->withToken($token)->getJson('/api/v1/stories')
            ->assertStatus(200);

        $response->assertJsonCount(1, 'data');
        $this->assertEquals($creator1->id, $response->json('data.0.creator_id'));
    }

    public function test_unsubscribed_user_gets_censored_premium_story(): void
    {
        $creator = $this->createCreator();
        $story   = Story::factory()->create(['creator_id' => $creator->id, 'media_id' => $this->createMedia($creator)->id, 'is_premium' => true]);
        
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        Follow::create(['follower_id' => $user->id, 'creator_id' => $creator->id]);

        $response = $this->withToken($token)->getJson('/api/v1/stories')
            ->assertStatus(200);

        $data = $response->json('data.0');
        
        $this->assertTrue($data['is_locked']);
        $this->assertArrayNotHasKey('media', $data); // Should be censored
    }

    public function test_creator_can_delete_their_story(): void
    {
        $creator = $this->createCreator();
        $story   = Story::factory()->create(['creator_id' => $creator->id, 'media_id' => $this->createMedia($creator)->id]);
        $token   = auth('api')->login($creator);

        $this->withToken($token)->deleteJson("/api/v1/stories/{$story->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('stories', ['id' => $story->id]);
    }

    public function test_expire_command_soft_deletes_expired_stories(): void
    {
        $creator = $this->createCreator();
        
        // Active story
        $active = Story::factory()->create(['creator_id' => $creator->id, 'media_id' => $this->createMedia($creator)->id, 'expires_at' => now()->addHour()]);
        // Expired story
        $expired = Story::factory()->create(['creator_id' => $creator->id, 'media_id' => $this->createMedia($creator)->id, 'expires_at' => now()->subHour()]);

        Artisan::call('stories:expire');

        $this->assertDatabaseHas('stories', ['id' => $active->id, 'deleted_at' => null]);
        $this->assertSoftDeleted('stories', ['id' => $expired->id]);
    }
}

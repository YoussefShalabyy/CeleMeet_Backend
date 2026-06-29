<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CreatorProfile;
use App\Models\Follow;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
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

    public function test_creator_can_create_a_free_post(): void
    {
        $creator = $this->createCreator();
        $token   = auth('api')->login($creator);

        $response = $this->withToken($token)->postJson('/api/v1/posts', [
            'content_type' => 'text',
            'caption'      => 'Hello world',
            'visibility'   => 'free',
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        
        $this->assertDatabaseHas('posts', [
            'creator_id' => $creator->id,
            'caption'    => 'Hello world',
            'visibility' => 'free',
        ]);
        
        $this->assertEquals(1, $creator->creatorProfile->fresh()->posts_count);
    }

    public function test_user_cannot_create_a_post(): void
    {
        $user  = User::factory()->create(['role' => 'regular']);
        $token = auth('api')->login($user);

        $this->withToken($token)->postJson('/api/v1/posts', [
            'content_type' => 'text',
            'caption'      => 'Hello world',
            'visibility'   => 'free',
        ])->assertStatus(422);
    }

    public function test_creator_can_attach_media_to_post(): void
    {
        $creator = $this->createCreator();
        $token   = auth('api')->login($creator);

        // Simulate an uploaded media asset owned by the creator
        $media = MediaAsset::create([
            'owner_id'    => $creator->id,
            'owner_type'  => User::class,
            'collection'  => 'post',
            'provider'    => 'cloudinary',
            'url'         => 'http://example.com/img.jpg',
        ]);

        $this->withToken($token)->postJson('/api/v1/posts', [
            'content_type' => 'image',
            'caption'      => 'My pic',
            'visibility'   => 'free',
            'media_ids'    => [$media->id],
        ])->assertStatus(201);

        // Verify media morph was updated to belong to the new Post
        $post = Post::first();
        $this->assertDatabaseHas('media_assets', [
            'id'         => $media->id,
            'owner_type' => Post::class,
            'owner_id'   => $post->id,
        ]);
    }

    public function test_creator_cannot_attach_someone_elses_media(): void
    {
        $creator = $this->createCreator();
        $other   = User::factory()->create();
        $token   = auth('api')->login($creator);

        $media = MediaAsset::create([
            'owner_id'    => $other->id,
            'owner_type'  => User::class,
            'collection'  => 'post',
            'provider'    => 'cloudinary',
            'url'         => 'http://example.com/img.jpg',
        ]);

        $this->withToken($token)->postJson('/api/v1/posts', [
            'content_type' => 'image',
            'visibility'   => 'free',
            'media_ids'    => [$media->id],
        ])->assertStatus(422); // BusinessException
    }

    public function test_user_can_view_free_post(): void
    {
        $creator = $this->createCreator();
        $post    = Post::factory()->create(['creator_id' => $creator->id, 'visibility' => 'free']);
        
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson("/api/v1/posts/{$post->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $post->id);
    }

    public function test_unsubscribed_user_gets_censored_premium_post_in_feed(): void
    {
        $creator = $this->createCreator();
        $post    = Post::factory()->create(['creator_id' => $creator->id, 'visibility' => 'premium', 'caption' => 'Secret']);
        
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        // User follows the creator so they see it in the feed
        Follow::create(['follower_id' => $user->id, 'creator_id' => $creator->id]);

        $response = $this->withToken($token)->getJson('/api/v1/posts')
            ->assertStatus(200);

        $data = $response->json('data.0');
        
        $this->assertTrue($data['is_locked']);
        $this->assertArrayNotHasKey('caption', $data); // Should be censored
        $this->assertArrayNotHasKey('media', $data);
    }

    public function test_unsubscribed_user_cannot_view_premium_post_directly(): void
    {
        $creator = $this->createCreator();
        $post    = Post::factory()->create(['creator_id' => $creator->id, 'visibility' => 'premium']);
        
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson("/api/v1/posts/{$post->id}")
            ->assertStatus(403);
    }

    public function test_feed_only_shows_posts_from_followed_creators(): void
    {
        $creator1 = $this->createCreator();
        $creator2 = $this->createCreator();
        
        Post::factory()->create(['creator_id' => $creator1->id]);
        Post::factory()->create(['creator_id' => $creator2->id]);

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        // Follow only creator 1
        Follow::create(['follower_id' => $user->id, 'creator_id' => $creator1->id]);

        $response = $this->withToken($token)->getJson('/api/v1/posts');
        
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($creator1->id, $response->json('data.0.creator_id'));
    }

    public function test_creator_can_update_their_post(): void
    {
        $creator = $this->createCreator();
        $post    = Post::factory()->create(['creator_id' => $creator->id, 'caption' => 'Old']);
        $token   = auth('api')->login($creator);

        $this->withToken($token)->putJson("/api/v1/posts/{$post->id}", [
            'caption'    => 'New',
            'visibility' => 'premium'
        ])->assertStatus(200);

        $this->assertEquals('New', $post->fresh()->caption);
        $this->assertEquals('premium', $post->fresh()->visibility);
    }

    public function test_creator_can_soft_delete_their_post(): void
    {
        $creator = $this->createCreator();
        $post    = Post::factory()->create(['creator_id' => $creator->id]);
        
        // Setup initial count
        $creator->creatorProfile->update(['posts_count' => 1]);

        $token   = auth('api')->login($creator);

        $this->withToken($token)->deleteJson("/api/v1/posts/{$post->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
        $this->assertEquals(0, $creator->creatorProfile->fresh()->posts_count);
    }
}

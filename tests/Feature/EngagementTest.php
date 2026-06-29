<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    private function createPost(): Post
    {
        $creator = User::factory()->create(['role' => 'celebrity']);
        \App\Models\CreatorProfile::create(['user_id' => $creator->id, 'display_name' => 'Creator']);
        
        return Post::factory()->create([
            'creator_id' => $creator->id,
            'is_active'  => true,
            'visibility' => 'free',
        ]);
    }

    public function test_user_can_like_a_post(): void
    {
        $post = $this->createPost();
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->postJson("/api/v1/posts/{$post->id}/like")
            ->assertStatus(200);

        $this->assertDatabaseHas('likes', [
            'user_id'       => $user->id,
            'likeable_type' => Post::class,
            'likeable_id'   => $post->id,
        ]);

        $this->assertEquals(1, $post->fresh()->likes_count);
    }

    public function test_user_can_unlike_a_post(): void
    {
        $post = $this->createPost();
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // Like first
        Like::create([
            'user_id'       => $user->id,
            'likeable_type' => Post::class,
            'likeable_id'   => $post->id,
        ]);

        $this->withToken($token)->deleteJson("/api/v1/posts/{$post->id}/like")
            ->assertStatus(200);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'likeable_id' => $post->id,
        ]);

        $this->assertEquals(0, $post->fresh()->likes_count);
    }

    public function test_liking_twice_is_idempotent(): void
    {
        $post = $this->createPost();
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->postJson("/api/v1/posts/{$post->id}/like")->assertStatus(200);
        $this->withToken($token)->postJson("/api/v1/posts/{$post->id}/like")->assertStatus(200);

        // Count should still be 1
        $this->assertEquals(1, Like::where('likeable_id', $post->id)->count());
        $this->assertEquals(1, $post->fresh()->likes_count);
    }

    public function test_user_can_add_comment(): void
    {
        $post = $this->createPost();
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson("/api/v1/posts/{$post->id}/comments", [
            'body' => 'Great post!',
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'body'    => 'Great post!',
        ]);

        $this->assertEquals(1, $post->fresh()->comments_count);
    }

    public function test_user_can_delete_their_own_comment(): void
    {
        $post = $this->createPost();
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'body'    => 'To be deleted',
        ]);

        $this->withToken($token)->deleteJson("/api/v1/comments/{$comment->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
        $this->assertEquals(0, $post->fresh()->comments_count);
    }

    public function test_user_cannot_delete_someone_elses_comment(): void
    {
        $post = $this->createPost();
        
        $user1 = User::factory()->create();
        $comment = Comment::create([
            'user_id' => $user1->id,
            'post_id' => $post->id,
            'body'    => 'Not yours',
        ]);

        $user2 = User::factory()->create();
        $token = auth('api')->login($user2);

        $this->withToken($token)->deleteJson("/api/v1/comments/{$comment->id}")
            ->assertStatus(403);
    }
}

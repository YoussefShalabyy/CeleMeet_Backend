<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CreatorProfile;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createCreator(): User
    {
        $creator = User::factory()->create(['role' => 'celebrity']);
        CreatorProfile::create([
            'user_id'      => $creator->id,
            'display_name' => 'Test Creator',
        ]);
        return $creator;
    }

    // ─── Tests ────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_follow_a_creator(): void
    {
        $user    = User::factory()->create();
        $creator = $this->createCreator();
        $token   = auth('api')->login($user);

        $this->withToken($token)
            ->postJson("/api/v1/creators/{$creator->id}/follow")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $user->id,
            'creator_id'  => $creator->id,
        ]);
    }

    public function test_following_a_creator_increments_followers_count(): void
    {
        $user    = User::factory()->create();
        $creator = $this->createCreator();
        $token   = auth('api')->login($user);

        $this->assertEquals(0, $creator->creatorProfile->followers_count);

        $this->withToken($token)
            ->postJson("/api/v1/creators/{$creator->id}/follow")
            ->assertStatus(200);

        $this->assertEquals(1, $creator->creatorProfile->fresh()->followers_count);
    }

    public function test_follow_is_idempotent_double_follow_does_not_double_increment(): void
    {
        $user    = User::factory()->create();
        $creator = $this->createCreator();
        $token   = auth('api')->login($user);

        $this->withToken($token)->postJson("/api/v1/creators/{$creator->id}/follow")->assertStatus(200);
        $this->withToken($token)->postJson("/api/v1/creators/{$creator->id}/follow")->assertStatus(200);

        $this->assertDatabaseCount('follows', 1);
        $this->assertEquals(1, $creator->creatorProfile->fresh()->followers_count);
    }

    public function test_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create(['role' => 'celebrity']);
        CreatorProfile::create(['user_id' => $user->id, 'display_name' => 'Self']);
        $token = auth('api')->login($user);

        $this->withToken($token)
            ->postJson("/api/v1/creators/{$user->id}/follow")
            ->assertStatus(422);

        $this->assertDatabaseCount('follows', 0);
    }

    public function test_authenticated_user_can_unfollow_a_creator(): void
    {
        $user    = User::factory()->create();
        $creator = $this->createCreator();
        $token   = auth('api')->login($user);

        // Follow first
        Follow::create(['follower_id' => $user->id, 'creator_id' => $creator->id]);

        $this->withToken($token)
            ->deleteJson("/api/v1/creators/{$creator->id}/follow")
            ->assertStatus(200);

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $user->id,
            'creator_id'  => $creator->id,
        ]);
    }

    public function test_unfollowing_decrements_followers_count(): void
    {
        $user    = User::factory()->create();
        $creator = $this->createCreator();
        $token   = auth('api')->login($user);

        Follow::create(['follower_id' => $user->id, 'creator_id' => $creator->id]);
        // Simulate the observer having already incremented the count
        $creator->creatorProfile->update(['followers_count' => 1]);

        $this->withToken($token)
            ->deleteJson("/api/v1/creators/{$creator->id}/follow")
            ->assertStatus(200);

        $this->assertEquals(0, $creator->creatorProfile->fresh()->followers_count);
    }

    public function test_unfollow_is_idempotent_when_not_following(): void
    {
        $user    = User::factory()->create();
        $creator = $this->createCreator();
        $token   = auth('api')->login($user);

        // Unfollow without ever following — must not error
        $this->withToken($token)
            ->deleteJson("/api/v1/creators/{$creator->id}/follow")
            ->assertStatus(200);
    }

    public function test_user_can_get_their_following_list(): void
    {
        $user     = User::factory()->create();
        $creator1 = $this->createCreator();
        $creator2 = $this->createCreator();
        $token    = auth('api')->login($user);

        Follow::create(['follower_id' => $user->id, 'creator_id' => $creator1->id]);
        Follow::create(['follower_id' => $user->id, 'creator_id' => $creator2->id]);

        $this->withToken($token)
            ->getJson('/api/v1/users/me/following')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_following_list_only_shows_creators_the_user_follows(): void
    {
        $user     = User::factory()->create();
        $followed = $this->createCreator();
        $other    = $this->createCreator();
        $token    = auth('api')->login($user);

        // Only follow one creator
        Follow::create(['follower_id' => $user->id, 'creator_id' => $followed->id]);

        $response = $this->withToken($token)
            ->getJson('/api/v1/users/me/following')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $data = $response->json('data');
        $this->assertEquals($followed->id, $data[0]['id']);
    }

    public function test_unauthenticated_user_cannot_follow(): void
    {
        $creator = $this->createCreator();

        $this->postJson("/api/v1/creators/{$creator->id}/follow")
            ->assertStatus(401);
    }

    public function test_follow_nonexistent_creator_returns_404(): void
    {
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)
            ->postJson('/api/v1/creators/99999/follow')
            ->assertStatus(404);
    }
}

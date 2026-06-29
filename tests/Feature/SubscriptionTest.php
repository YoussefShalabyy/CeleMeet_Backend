<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CreatorProfile;
use App\Models\Post;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function createCreator(): User
    {
        $creator = User::factory()->create(['role' => 'celebrity']);
        CreatorProfile::create(['user_id' => $creator->id, 'display_name' => 'Test Creator']);
        return $creator;
    }

    private function createPlan(User $creator, int $coins = 100, int $days = 30): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'creator_id'    => $creator->id,
            'title'         => 'Premium Plan',
            'coins'         => $coins,
            'duration_days' => $days,
            'is_active'     => true,
        ]);
    }

    public function test_creator_can_create_plan(): void
    {
        $creator = $this->createCreator();
        $token = auth('api')->login($creator);

        $response = $this->withToken($token)->postJson('/api/v1/creator/subscription-plan', [
            'title'         => 'Super Fan',
            'coins'         => 500,
            'duration_days' => 30,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscription_plans', [
            'creator_id' => $creator->id,
            'coins'      => 500,
        ]);
    }

    public function test_user_can_subscribe_to_creator_and_coins_are_deducted(): void
    {
        $creator = $this->createCreator();
        $plan = $this->createPlan($creator, 100);

        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['available_balance' => 150]);
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/v1/subscriptions', [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('subscriptions', [
            'subscriber_id' => $user->id,
            'plan_id'       => $plan->id,
            'status'        => 'active',
        ]);

        $this->assertEquals(50, Wallet::where('user_id', $user->id)->value('available_balance'));
    }

    public function test_user_cannot_subscribe_with_insufficient_funds(): void
    {
        $creator = $this->createCreator();
        $plan = $this->createPlan($creator, 100);

        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['available_balance' => 50]); // Not enough!
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/v1/subscriptions', [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(422); // BusinessException
        $this->assertEquals(50, Wallet::where('user_id', $user->id)->value('available_balance')); // No deduction
    }

    public function test_user_cannot_subscribe_to_themselves(): void
    {
        $creator = $this->createCreator();
        $plan = $this->createPlan($creator, 100);
        Wallet::where('user_id', $creator->id)->update(['available_balance' => 500]);
        $token = auth('api')->login($creator);

        $this->withToken($token)->postJson('/api/v1/subscriptions', [
            'plan_id' => $plan->id,
        ])->assertStatus(403);
    }

    public function test_expire_command_updates_expired_subscriptions(): void
    {
        $creator = $this->createCreator();
        $plan = $this->createPlan($creator);
        $user = User::factory()->create();

        // Expired subscription
        $sub = Subscription::create([
            'plan_id'       => $plan->id,
            'creator_id'    => $creator->id,
            'subscriber_id' => $user->id,
            'expires_at'    => now()->subDay(),
            'status'        => 'active',
        ]);

        Artisan::call('subscriptions:expire');

        $this->assertEquals('expired', $sub->fresh()->status);
    }
}

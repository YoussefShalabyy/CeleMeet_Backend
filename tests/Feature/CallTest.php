<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ServiceType;
use App\Models\CallSession;
use App\Models\CreatorProfile;
use App\Models\CreatorService;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallTest extends TestCase
{
    use RefreshDatabase;

    private function createCreatorWithService(): User
    {
        $creator = User::factory()->create(['role' => 'celebrity']);
        CreatorProfile::create(['user_id' => $creator->id, 'display_name' => 'Test Creator']);
        
        CreatorService::create([
            'creator_id' => $creator->id,
            'service_type' => ServiceType::VoiceCall,
            'price_in_coins' => 10, // 10 coins per minute
            'is_enabled' => true,
        ]);
        
        return $creator;
    }

    public function test_user_can_get_call_token(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/v1/calls/token');

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'token', 'apiKey', 'user']);
    }

    public function test_user_cannot_initiate_call_without_minimum_hold(): void
    {
        $creator = $this->createCreatorWithService();
        $user = User::factory()->create();
        // 10 coins * 5 minutes = 50 coins required. User only has 40.
        Wallet::where('user_id', $user->id)->update(['available_balance' => 40]);
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/v1/calls/initiate', [
            'callee_id' => $creator->id,
            'call_type' => 'voice',
        ]);

        $response->assertStatus(422); // BusinessException: Insufficient balance
    }

    public function test_user_can_initiate_call_with_sufficient_balance(): void
    {
        $creator = $this->createCreatorWithService();
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['available_balance' => 100]);
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/v1/calls/initiate', [
            'callee_id' => $creator->id,
            'call_type' => 'voice',
        ]);

        $response->assertStatus(201);
        
        // Ensure 50 coins are moved to held
        $this->assertEquals(50, Wallet::where('user_id', $user->id)->value('available_balance'));
        $this->assertEquals(50, Wallet::where('user_id', $user->id)->value('held_balance'));

        $this->assertDatabaseHas('call_sessions', [
            'caller_id' => $user->id,
            'callee_id' => $creator->id,
            'status' => 'initiated',
        ]);
    }

    public function test_user_can_end_call_and_be_billed_correctly(): void
    {
        $creator = $this->createCreatorWithService();
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['available_balance' => 100]);
        $token = auth('api')->login($user);

        $initiateResponse = $this->withToken($token)->postJson('/api/v1/calls/initiate', [
            'callee_id' => $creator->id,
            'call_type' => 'voice',
        ]);

        $sessionId = $initiateResponse->json('data.id');

        // End call after 61 seconds (2 minutes = 20 coins)
        $response = $this->withToken($token)->postJson("/api/v1/calls/{$sessionId}/end", [
            'duration_seconds' => 61,
        ]);

        $response->assertStatus(200);

        // 100 total - 20 billed = 80 remaining
        $this->assertEquals(80, Wallet::where('user_id', $user->id)->value('available_balance'));
        $this->assertEquals(0, Wallet::where('user_id', $user->id)->value('held_balance'));

        $this->assertDatabaseHas('call_sessions', [
            'id' => $sessionId,
            'status' => 'completed',
            'total_coins_charged' => 20,
        ]);
    }
}

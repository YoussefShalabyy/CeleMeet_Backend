<?php

declare(strict_types=1);

namespace Tests\Feature\Wallet;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_wallet(): void
    {
        $this->getJson('/api/v1/wallet')->assertStatus(401);
    }

    public function test_user_can_view_own_wallet(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // Update balance just to assert it's returned
        $user->wallet->update(['available_balance' => 150]);

        $this->withToken($token)
            ->getJson('/api/v1/wallet')
            ->assertStatus(200)
            ->assertJsonPath('data.available_balance', 150);
    }

    public function test_user_can_view_transactions(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $user->wallet->walletTransactions()->create([
            'user_id' => $user->id,
            'amount' => 50,
            'transaction_type' => 'recharge',
            'status' => 'completed',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/wallet/transactions')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}

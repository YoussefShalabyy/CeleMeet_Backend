<?php

declare(strict_types=1);

namespace Tests\Feature\Wallet;

use App\Models\CoinPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoinPackageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_active_coin_packages(): void
    {
        CoinPackage::factory()->create(['is_active' => true]);
        CoinPackage::factory()->create(['is_active' => false]); // Should not be returned

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)
            ->getJson('/api/v1/coin-packages')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_non_admin_cannot_create_coin_package(): void
    {
        $user = User::factory()->create(['role' => 'regular']);
        $token = auth('api')->login($user);

        $this->withToken($token)
            ->postJson('/api/v1/admin/coin-packages', [
                'coins' => 100,
                'price' => 1.99,
                'currency' => 'USD'
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_create_coin_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = auth('api')->login($admin);

        $this->withToken($token)
            ->postJson('/api/v1/admin/coin-packages', [
                'coins' => 500,
                'bonus_coins' => 50,
                'price' => 4.99,
                'currency' => 'USD'
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.coins', 500);

        $this->assertDatabaseHas('coin_packages', ['coins' => 500]);
    }
}

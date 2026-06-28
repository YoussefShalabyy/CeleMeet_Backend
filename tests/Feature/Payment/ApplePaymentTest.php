<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Models\CoinPackage;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApplePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_apple_iap_receipt_verifies_and_credits_wallet(): void
    {
        config(['services.apple.bundle_id' => 'com.test.app']);
        config(['services.apple.shared_secret' => 'secret']);
        
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        CoinPackage::factory()->create([
            'coins' => 100,
            'bonus_coins' => 0,
            'price' => 1.99,
        ]);

        Http::fake([
            '*verifyReceipt' => Http::response([
                'status' => 0,
                'receipt' => [
                    'bundle_id' => 'com.test.app',
                    'in_app' => [
                        [
                            'transaction_id' => 'TEST_TX_123',
                            'product_id' => 'com.test.coins.100',
                            'purchase_date_ms' => '123456789',
                        ]
                    ]
                ]
            ])
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/payments/apple/verify', [
                'receipt_data' => 'base64_fake_receipt',
            ])
            ->assertStatus(200);

        // Assert payment tx was created
        $this->assertDatabaseHas('payment_transactions', [
            'user_id' => $user->id,
            'provider' => 'apple',
            'provider_transaction_id' => 'TEST_TX_123',
            'status' => 'completed',
        ]);

        // Assert wallet credited
        $wallet = $user->wallet;
        $this->assertEquals(100, $wallet->available_balance);
    }

    public function test_apple_iap_prevents_duplicate_receipt_use(): void
    {
        config(['services.apple.bundle_id' => 'com.test.app']);
        config(['services.apple.shared_secret' => 'secret']);
        
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // Simulate an existing transaction with this ID
        PaymentTransaction::create([
            'user_id' => $user->id,
            'provider' => 'apple',
            'provider_transaction_id' => 'TEST_TX_DUPLICATE',
            'amount' => 1.99,
            'currency' => 'USD',
            'coins' => 100,
            'status' => 'completed',
        ]);

        Http::fake([
            '*verifyReceipt' => Http::response([
                'status' => 0,
                'receipt' => [
                    'bundle_id' => 'com.test.app',
                    'in_app' => [
                        [
                            'transaction_id' => 'TEST_TX_DUPLICATE',
                            'product_id' => 'com.test.coins.100',
                            'purchase_date_ms' => '123456789',
                        ]
                    ]
                ]
            ])
        ]);

        // Second time using this receipt should fail
        $this->withToken($token)
            ->postJson('/api/v1/payments/apple/verify', [
                'receipt_data' => 'base64_fake_receipt',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Receipt has already been processed.');
    }
}

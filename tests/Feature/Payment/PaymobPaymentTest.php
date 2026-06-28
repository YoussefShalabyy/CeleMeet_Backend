<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Models\CoinPackage;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymobPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.paymob.api_key' => 'fake_api_key',
            'services.paymob.hmac_secret' => 'secret',
            'services.paymob.integration_id' => '123',
        ]);
    }

    public function test_user_can_initiate_paymob_payment(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        
        $package = CoinPackage::factory()->create([
            'coins' => 100,
            'price' => 1.99,
        ]);

        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response(['token' => 'auth_token']),
            'accept.paymob.com/api/ecommerce/orders' => Http::response(['id' => '12345']),
            'accept.paymob.com/api/acceptance/payment_keys' => Http::response(['token' => 'payment_token']),
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/payments/paymob/initiate', [
                'coin_package_id' => $package->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.checkout_url', 'https://accept.paymob.com/api/acceptance/iframes/your_iframe_id?payment_token=payment_token');

        $this->assertDatabaseHas('payment_transactions', [
            'user_id' => $user->id,
            'provider_transaction_id' => '12345',
            'amount' => 1.99,
            'status' => 'pending',
        ]);
    }

    public function test_paymob_webhook_credits_user_wallet_if_successful(): void
    {
        config(['services.paymob.hmac_secret' => 'secret']);
        $user = User::factory()->create();
        
        $tx = PaymentTransaction::create([
            'user_id' => $user->id,
            'provider' => 'paymob',
            'provider_transaction_id' => '999',
            'amount' => 5.00,
            'currency' => 'USD',
            'coins' => 500,
            'status' => 'pending',
        ]);

        $payload = [
            'obj' => [
                'id' => 12345,
                'pending' => false,
                'amount_cents' => 5000,
                'success' => true,
                'is_auth' => false,
                'is_capture' => false,
                'is_standalone_payment' => true,
                'is_voided' => false,
                'is_refunded' => false,
                'is_3d_secure' => true,
                'integration_id' => 123,
                'error_occured' => false,
                'has_parent_transaction' => false,
                'currency' => 'USD',
                'created_at' => '2026-01-01T00:00:00.000000Z',
                'order' => [
                    'id' => 999,
                    'merchant_order_id' => $tx->id . '_RAND',
                ],
                'source_data' => [
                    'pan' => '1234',
                    'sub_type' => 'MasterCard',
                    'type' => 'card',
                ]
            ]
        ];

        // Compute HMAC dynamically using same keys
        $hmacString = '';
        $keys = [
            'amount_cents', 'created_at', 'currency', 'error_occured',
            'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
            'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
            'is_voided', 'order', 'owner', 'pending', 'source_data_pan',
            'source_data_sub_type', 'source_data_type', 'success'
        ];
        $obj = $payload['obj'];
        foreach ($keys as $key) {
            $value = match ($key) {
                'order' => $obj['order']['id'] ?? '',
                'source_data_pan' => $obj['source_data']['pan'] ?? '',
                'source_data_sub_type' => $obj['source_data']['sub_type'] ?? '',
                'source_data_type' => $obj['source_data']['type'] ?? '',
                default => $obj[$key] ?? ''
            };
            if (is_bool($value)) $value = $value ? 'true' : 'false';
            $hmacString .= $value;
        }

        $signature = hash_hmac('sha512', $hmacString, 'secret');

        $this->postJson('/api/v1/payments/paymob/webhook?hmac=' . $signature, $payload)
            ->assertStatus(200);

        $tx->refresh();
        $this->assertEquals('completed', $tx->status->value);

        // Assert Wallet was credited
        $wallet = $user->wallet;
        $this->assertEquals(500, $wallet->available_balance);

        // Assert WalletTransaction created
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'amount' => 500,
            'transaction_type' => 'recharge',
            'reference_id' => $tx->id,
            'reference_type' => PaymentTransaction::class,
        ]);
    }
}

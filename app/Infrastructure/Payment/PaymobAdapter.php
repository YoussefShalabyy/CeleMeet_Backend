<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class PaymobAdapter implements PaymentGatewayInterface
{
    private string $apiKey;
    private string $hmacSecret;
    private string $integrationId;
    private string $baseUrl = 'https://accept.paymob.com/api';

    public function __construct()
    {
        $this->apiKey = config('services.paymob.api_key') ?? '';
        $this->hmacSecret = config('services.paymob.hmac_secret') ?? '';
        $this->integrationId = config('services.paymob.integration_id') ?? '';
    }

    public function initiatePayment(int $amountCents, string $currency, string $orderId, array $customerData, array $metadata = []): array
    {
        if (empty($this->apiKey) || empty($this->integrationId)) {
            throw new \RuntimeException('Paymob keys not configured');
        }

        // 1. Auth to get token
        $authRes = Http::post("{$this->baseUrl}/auth/tokens", ['api_key' => $this->apiKey]);
        $token = $authRes->json('token');

        // 2. Register Order
        $orderRes = Http::post("{$this->baseUrl}/ecommerce/orders", [
            'auth_token'      => $token,
            'delivery_needed' => 'false',
            'amount_cents'    => $amountCents,
            'currency'        => $currency,
            'merchant_order_id' => $orderId . '_' . Str::random(5), // ensure unique
            'items'           => [],
        ]);
        $paymobOrderId = (string) $orderRes->json('id');

        // 3. Generate Payment Key
        $paymentKeyRes = Http::post("{$this->baseUrl}/acceptance/payment_keys", [
            'auth_token' => $token,
            'amount_cents' => $amountCents,
            'expiration' => 3600,
            'order_id' => $paymobOrderId,
            'billing_data' => [
                'apartment' => 'NA',
                'email' => $customerData['email'] ?? 'test@example.com',
                'floor' => 'NA',
                'first_name' => $customerData['first_name'] ?? 'NA',
                'street' => 'NA',
                'building' => 'NA',
                'phone_number' => $customerData['phone'] ?? '+201000000000',
                'shipping_method' => 'NA',
                'postal_code' => 'NA',
                'city' => 'NA',
                'country' => 'NA',
                'last_name' => $customerData['last_name'] ?? 'NA',
                'state' => 'NA'
            ],
            'currency' => $currency,
            'integration_id' => $this->integrationId
        ]);
        $paymentToken = $paymentKeyRes->json('token');

        return [
            'checkout_url' => "https://accept.paymob.com/api/acceptance/iframes/your_iframe_id?payment_token={$paymentToken}", // Note: iFrame ID usually hardcoded or passed via config
            'provider_transaction_id' => $paymobOrderId,
        ];
    }

    public function parseCallback(array $payload, string $signature): array
    {
        // 1. Calculate HMAC
        $hmacString = '';
        $keys = [
            'amount_cents', 'created_at', 'currency', 'error_occured',
            'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
            'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
            'is_voided', 'order', 'owner', 'pending', 'source_data_pan',
            'source_data_sub_type', 'source_data_type', 'success'
        ];

        $obj = $payload['obj'] ?? [];

        foreach ($keys as $key) {
            $value = match ($key) {
                'order' => $obj['order']['id'] ?? '',
                'source_data_pan' => $obj['source_data']['pan'] ?? '',
                'source_data_sub_type' => $obj['source_data']['sub_type'] ?? '',
                'source_data_type' => $obj['source_data']['type'] ?? '',
                default => $obj[$key] ?? ''
            };
            
            // Boolean normalization for Paymob HMAC
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            
            $hmacString .= $value;
        }

        $calculatedHmac = hash_hmac('sha512', $hmacString, $this->hmacSecret);

        if (! hash_equals($calculatedHmac, $signature)) {
            throw new BusinessException('Invalid payment signature');
        }

        $merchantOrderId = $obj['order']['merchant_order_id'] ?? '';
        $internalOrderId = Str::beforeLast($merchantOrderId, '_'); // Strip the random suffix

        return [
            'provider_transaction_id' => (string) ($obj['id'] ?? ''),
            'status' => ($obj['success'] ?? false) ? 'completed' : 'failed',
            'amount_cents' => (int) ($obj['amount_cents'] ?? 0),
            'currency' => $obj['currency'] ?? 'USD',
            'order_id' => $internalOrderId,
            'is_success' => (bool) ($obj['success'] ?? false),
        ];
    }
}

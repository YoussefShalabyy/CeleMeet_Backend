<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\BusinessException;

/**
 * Fake implementation of PaymentGatewayInterface for development and testing.
 *
 * Simulates a successful payment flow without any real money movement.
 * Use FAKE_PAYMENT_SUCCESS=false in tests to simulate failures.
 */
class FakePaymentGateway implements PaymentGatewayInterface
{
    public function initiatePayment(
        int $amountCents,
        string $currency,
        string $orderId,
        array $customerData,
        array $metadata = [],
    ): array {
        return [
            'checkout_url' => 'https://fake-payment.local/pay/'.$orderId,
            'provider_transaction_id' => 'fake_txn_'.uniqid(),
        ];
    }

    /**
     * @throws BusinessException When 'invalid' is passed as the signature (for testing failure paths).
     */
    public function parseCallback(array $payload, string $signature): array
    {
        if ($signature === 'invalid') {
            throw new BusinessException('Invalid payment signature.');
        }

        return [
            'provider_transaction_id' => $payload['provider_transaction_id'] ?? 'fake_txn_'.uniqid(),
            'status' => $payload['status'] ?? 'completed',
            'amount_cents' => $payload['amount_cents'] ?? 0,
            'currency' => $payload['currency'] ?? 'EGP',
            'order_id' => $payload['order_id'] ?? '',
            'is_success' => ($payload['status'] ?? 'completed') === 'completed',
        ];
    }
}

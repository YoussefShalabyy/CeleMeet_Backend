<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for payment gateway implementations (e.g. Paymob, Stripe).
 *
 * Business logic must ONLY depend on this interface.
 * Never import payment SDK classes outside of Infrastructure/Payment/.
 */
interface PaymentGatewayInterface
{
    /**
     * Initiate a payment and return a checkout URL for the user.
     *
     * @param  int  $amountCents  The amount in the smallest currency unit (e.g., piastres for EGP).
     * @param  string  $currency  ISO 4217 currency code (e.g., 'EGP', 'USD').
     * @param  string  $orderId  Our internal order/payment reference.
     * @param  array<string, mixed>  $customerData  Customer details (name, email, phone).
     * @param  array<string, mixed>  $metadata  Any extra data to attach.
     * @return array{
     *     checkout_url: string,
     *     provider_transaction_id: string
     * }
     */
    public function initiatePayment(
        int $amountCents,
        string $currency,
        string $orderId,
        array $customerData,
        array $metadata = [],
    ): array;

    /**
     * Verify and parse a payment callback/webhook payload.
     *
     * @param  array<string, mixed>  $payload  The raw callback data.
     * @param  string  $signature  HMAC or signature from the provider.
     * @return array{
     *     provider_transaction_id: string,
     *     status: string,
     *     amount_cents: int,
     *     currency: string,
     *     order_id: string,
     *     is_success: bool
     * }
     *
     * @throws \App\Exceptions\BusinessException When the signature is invalid.
     */
    public function parseCallback(array $payload, string $signature): array;
}

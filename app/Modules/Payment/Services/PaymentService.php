<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Exceptions\BusinessException;
use App\Models\CoinPackage;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Modules\Wallet\DTOs\CreditWalletDTO;
use App\Modules\Wallet\Services\WalletService;
use App\Infrastructure\Payment\PaymobAdapter;
use App\Infrastructure\Payment\AppleIapAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PaymentService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly PaymobAdapter $paymobAdapter,
        private readonly AppleIapAdapter $appleAdapter
    ) {}

    public function initiatePaymobPayment(User $user, int $coinPackageId): string
    {
        $package = CoinPackage::where('is_active', true)->findOrFail($coinPackageId);

        // Record the attempt
        $paymentTx = PaymentTransaction::create([
            'user_id' => $user->id,
            'provider' => PaymentProvider::Paymob,
            'amount' => $package->price,
            'currency' => $package->currency,
            'coins' => $package->coins + $package->bonus_coins,
            'status' => PaymentStatus::Pending,
        ]);

        $amountCents = (int) round($package->price * 100);

        $customerData = [
            'first_name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone_number ?? '+201000000000',
        ];

        // Call Paymob
        $response = $this->paymobAdapter->initiatePayment(
            amountCents: $amountCents,
            currency: $package->currency,
            orderId: (string) $paymentTx->id,
            customerData: $customerData
        );

        // Update with provider ID
        $paymentTx->update([
            'provider_transaction_id' => $response['provider_transaction_id'],
        ]);

        return $response['checkout_url'];
    }

    public function handlePaymobWebhook(array $payload, string $signature): void
    {
        try {
            $parsed = $this->paymobAdapter->parseCallback($payload, $signature);
        } catch (\Exception $e) {
            Log::error('Paymob Webhook HMAC Verification Failed', ['error' => $e->getMessage()]);
            throw new BusinessException('Invalid signature');
        }

        $paymentTxId = (int) $parsed['order_id'];
        
        DB::transaction(function () use ($parsed, $paymentTxId, $payload) {
            $tx = PaymentTransaction::lockForUpdate()->find($paymentTxId);
            
            if (!$tx) {
                Log::warning('Paymob webhook received for unknown transaction', ['id' => $paymentTxId]);
                return;
            }

            if ($tx->status !== PaymentStatus::Pending) {
                Log::info('Paymob webhook received for already processed transaction', ['id' => $paymentTxId]);
                return;
            }

            $tx->raw_response = $payload;

            if ($parsed['is_success']) {
                $tx->status = PaymentStatus::Completed;
                $tx->save();

                $this->walletService->credit(new CreditWalletDTO(
                    userId: $tx->user_id,
                    amount: $tx->coins,
                    transactionType: TransactionType::Recharge,
                    referenceId: $tx->id,
                    referenceType: PaymentTransaction::class,
                    description: 'Coin recharge via Paymob'
                ));
            } else {
                $tx->status = PaymentStatus::Failed;
                $tx->save();
            }
        });
    }

    public function verifyAppleReceipt(User $user, string $receiptData): void
    {
        $verification = $this->appleAdapter->verifyReceipt($receiptData);

        // Prevent replay attacks (using the same receipt twice)
        $exists = PaymentTransaction::where('provider', PaymentProvider::Apple)
            ->where('provider_transaction_id', $verification['provider_transaction_id'])
            ->exists();

        if ($exists) {
            throw new BusinessException('Receipt has already been processed.');
        }

        // We assume Apple product IDs map perfectly to a CoinPackage in our DB.
        // For example, product_id 'com.celemeet.coins.500' maps to the package.
        // For simplicity, we extract the numeric coins part, or look it up.
        // Usually, the database stores the apple_product_id in the coin_packages table, 
        // but here we'll just try to find the package based on standard pricing or hardcode mapping if not present.
        // Let's assume you have a way to map them. Since we didn't add apple_product_id to CoinPackage, we'll try parsing.

        $productId = $verification['product_id'];
        $coinsMatch = [];
        preg_match('/(\d+)/', $productId, $coinsMatch);
        $coins = !empty($coinsMatch[1]) ? (int) $coinsMatch[1] : 0;

        $package = CoinPackage::where('coins', $coins)->first();
        if (!$package) {
            Log::error('Unrecognized Apple product ID', ['product_id' => $productId]);
            throw new BusinessException('Unrecognized Apple product ID.');
        }

        DB::transaction(function () use ($user, $verification, $package) {
            $tx = PaymentTransaction::create([
                'user_id' => $user->id,
                'provider' => PaymentProvider::Apple,
                'provider_transaction_id' => $verification['provider_transaction_id'],
                'amount' => $package->price,
                'currency' => $package->currency,
                'coins' => $package->coins + $package->bonus_coins,
                'status' => PaymentStatus::Completed,
                'raw_response' => $verification['raw_receipt'],
            ]);

            $this->walletService->credit(new CreditWalletDTO(
                userId: $user->id,
                amount: $tx->coins,
                transactionType: TransactionType::Recharge,
                referenceId: $tx->id,
                referenceType: PaymentTransaction::class,
                description: 'Coin recharge via Apple In-App Purchase'
            ));
        });
    }
}

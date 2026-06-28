<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Http;

final class AppleIapAdapter
{
    private string $sharedSecret;
    private string $bundleId;

    public function __construct()
    {
        $this->sharedSecret = config('services.apple.shared_secret') ?? '';
        $this->bundleId = config('services.apple.bundle_id') ?? '';
    }

    /**
     * Verify an Apple App Store receipt.
     *
     * @return array{
     *     provider_transaction_id: string,
     *     product_id: string,
     *     is_success: bool,
     *     raw_receipt: array
     * }
     */
    public function verifyReceipt(string $receiptData, bool $isSandbox = false): array
    {
        if (empty($this->sharedSecret)) {
            throw new \RuntimeException('Apple shared secret not configured');
        }

        $url = $isSandbox 
            ? 'https://sandbox.itunes.apple.com/verifyReceipt'
            : 'https://buy.itunes.apple.com/verifyReceipt';

        $response = Http::post($url, [
            'receipt-data' => $receiptData,
            'password'     => $this->sharedSecret,
            'exclude-old-transactions' => true,
        ]);

        $data = $response->json();

        // 21007 means receipt is for sandbox but sent to production. We should retry on sandbox automatically.
        if (isset($data['status']) && $data['status'] === 21007 && !$isSandbox) {
            return $this->verifyReceipt($receiptData, true);
        }

        if (! isset($data['status']) || $data['status'] !== 0) {
            throw new BusinessException('Invalid Apple Receipt. Status: ' . ($data['status'] ?? 'unknown'));
        }

        $receipt = $data['receipt'] ?? [];
        if (($receipt['bundle_id'] ?? '') !== $this->bundleId) {
            throw new BusinessException('Receipt bundle ID mismatch');
        }

        $inApp = $receipt['in_app'] ?? [];
        if (empty($inApp)) {
            throw new BusinessException('No in-app purchases found in receipt');
        }

        // We take the latest transaction for this verification request
        $latestTx = collect($inApp)->sortByDesc('purchase_date_ms')->first();

        return [
            'provider_transaction_id' => (string) $latestTx['transaction_id'],
            'product_id' => $latestTx['product_id'],
            'is_success' => true,
            'raw_receipt' => $data,
        ];
    }
}

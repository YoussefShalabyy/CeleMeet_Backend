<?php

declare(strict_types=1);

namespace App\Modules\Chat\Services;

use App\Enums\TransactionType;
use App\Exceptions\BusinessException;
use App\Models\MessageRefund;
use App\Models\PaidMessage;
use App\Modules\Wallet\DTOs\CreditWalletDTO;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Support\Facades\DB;

final class MessageRefundService
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    public function processRefund(int $paidMessageId): MessageRefund
    {
        return DB::transaction(function () use ($paidMessageId) {
            $message = PaidMessage::lockForUpdate()->findOrFail($paidMessageId);

            if ($message->status === 'refunded') {
                throw new BusinessException('This message has already been refunded.');
            }

            if ($message->status !== 'sent') {
                throw new BusinessException('Only unanswered messages can be refunded.');
            }

            if (now()->lessThan($message->refund_eligible_until)) {
                throw new BusinessException('This message is not yet eligible for a refund.');
            }

            $message->update(['status' => 'refunded']);

            // Credit Coins
            $this->walletService->credit(new CreditWalletDTO(
                userId: $message->sender_id,
                amount: $message->price_in_coins,
                transactionType: TransactionType::Refund,
                referenceId: $message->id,
                referenceType: PaidMessage::class,
                description: "Refund for unanswered message {$message->id}"
            ));

            return MessageRefund::create([
                'paid_message_id' => $message->id,
                'user_id'         => $message->sender_id,
                'coins_returned'  => $message->price_in_coins,
                'reason'          => 'no_reply',
            ]);
        });
    }
}

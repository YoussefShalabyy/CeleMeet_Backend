<?php

declare(strict_types=1);

namespace App\Modules\Chat\Services;

use App\Contracts\ChatProviderInterface;
use App\Enums\TransactionType;
use App\Exceptions\BusinessException;
use App\Models\CreatorProfile;
use App\Models\PaidMessage;
use App\Models\User;
use App\Modules\Chat\DTOs\SendMessageDTO;
use App\Modules\Wallet\DTOs\DeductWalletDTO;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Support\Facades\DB;

final class MessageService
{
    // A standard cost for sending a message. In the future, this could be configurable per creator.
    private const MESSAGE_PRICE = 10;
    
    // How long until a message is eligible for a refund if unanswered.
    private const REFUND_ELIGIBILITY_HOURS = 24;

    public function __construct(
        private readonly ChatProviderInterface $chatProvider,
        private readonly WalletService $walletService
    ) {}

    public function generateToken(User $user): string
    {
        $this->chatProvider->upsertUser(
            $user->id,
            $user->creatorProfile ? $user->creatorProfile->display_name : $user->username,
            $user->avatar ? $user->avatar->url : null
        );

        return $this->chatProvider->generateUserToken($user->id);
    }

    public function send(SendMessageDTO $dto): PaidMessage
    {
        // Must send to a valid creator
        $creator = CreatorProfile::where('user_id', $dto->receiverId)->first();
        if (!$creator) {
            throw new BusinessException('Receiver is not a valid creator.');
        }

        if ($dto->senderId === $dto->receiverId) {
            throw new BusinessException('You cannot send a paid message to yourself.');
        }

        return DB::transaction(function () use ($dto, $creator) {
            // Deduct Coins
            $this->walletService->deduct(new DeductWalletDTO(
                userId: $dto->senderId,
                amount: self::MESSAGE_PRICE,
                transactionType: TransactionType::Message,
                description: "Paid message to creator {$dto->receiverId}"
            ));

            // Generate channel ID for 1-on-1 chat
            // Sort IDs to ensure consistency (e.g. "chat-1-2" regardless of who initiates)
            $ids = [$dto->senderId, $dto->receiverId];
            sort($ids);
            $channelId = "chat-{$ids[0]}-{$ids[1]}";

            // Sync channel in Stream
            $this->chatProvider->getOrCreateChannel($channelId, $dto->receiverId, $dto->senderId);

            // Save record to our DB
            $paidMessage = PaidMessage::create([
                'sender_id'             => $dto->senderId,
                'receiver_id'           => $dto->receiverId,
                'external_channel_id'   => $channelId,
                'message_type'          => $dto->messageType,
                'content'               => $dto->content,
                'media_asset_id'        => $dto->mediaAssetId,
                'price_in_coins'        => self::MESSAGE_PRICE,
                'status'                => 'sent',
                'refund_eligible_until' => now()->addHours(self::REFUND_ELIGIBILITY_HOURS),
            ]);

            // Now push to stream using the ID as metadata
            try {
                $streamMessageId = $this->chatProvider->sendMessage(
                    $channelId,
                    $dto->senderId,
                    $dto->content,
                    ['paid_message_id' => $paidMessage->id]
                );

                $paidMessage->update(['external_message_id' => $streamMessageId]);
            } catch (\Exception $e) {
                // If stream fails, the DB transaction will rollback, 
                // and the user will get their coins back.
                throw new BusinessException('Failed to deliver message via chat provider.');
            }

            return $paidMessage;
        });
    }
}

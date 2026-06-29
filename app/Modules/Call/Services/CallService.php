<?php

declare(strict_types=1);

namespace App\Modules\Call\Services;

use App\Contracts\VideoCallProviderInterface;
use App\Enums\ServiceType;
use App\Enums\TransactionType;
use App\Exceptions\BusinessException;
use App\Models\CallSession;
use App\Models\CreatorService;
use App\Models\User;
use App\Modules\Call\DTOs\EndCallDTO;
use App\Modules\Call\DTOs\InitiateCallDTO;
use App\Modules\Wallet\DTOs\DeductWalletDTO;
use App\Modules\Wallet\DTOs\HoldWalletDTO;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Support\Facades\DB;

final class CallService
{
    private const MINIMUM_HOLD_MINUTES = 5;

    public function __construct(
        private readonly VideoCallProviderInterface $callProvider,
        private readonly WalletService $walletService
    ) {}

    public function generateToken(User $user): string
    {
        return $this->callProvider->generateUserToken($user->id);
    }

    public function initiate(InitiateCallDTO $dto): CallSession
    {
        if ($dto->callerId === $dto->calleeId) {
            throw new BusinessException('You cannot call yourself.');
        }

        $serviceTypeEnum = match ($dto->callType) {
            'voice' => ServiceType::VoiceCall,
            'video' => ServiceType::VideoCall,
            default => throw new BusinessException('Invalid call type.'),
        };

        // Find the creator's configured rate
        $creatorService = CreatorService::where('creator_id', $dto->calleeId)
            ->where('service_type', $serviceTypeEnum->value)
            ->where('is_enabled', true)
            ->first();

        if (!$creatorService) {
            throw new BusinessException('The creator does not have this call type enabled.');
        }

        $ratePerMinute = $creatorService->price_in_coins;
        $requiredHold = $ratePerMinute * self::MINIMUM_HOLD_MINUTES;

        return DB::transaction(function () use ($dto, $ratePerMinute, $requiredHold) {
            $ids = [$dto->callerId, $dto->calleeId];
            sort($ids);
            $callId = "call_{$dto->callType}_{$ids[0]}_{$ids[1]}_" . time();

            // 1. Create the Database Record first to get the ID for reference
            $session = CallSession::create([
                'caller_id'           => $dto->callerId,
                'callee_id'           => $dto->calleeId,
                'call_type'           => $dto->callType,
                'rate_per_minute'     => $ratePerMinute,
                'status'              => 'initiated',
            ]);

            // 2. Hold coins
            $this->walletService->hold(new HoldWalletDTO(
                userId: $dto->callerId,
                amount: (int) $requiredHold,
                referenceId: $session->id,
                referenceType: CallSession::class,
                description: "Hold for {$dto->callType} call to {$dto->calleeId}"
            ));

            // 3. Create the Stream Call Session ID
            $externalSessionId = $this->callProvider->createCall(
                $callId,
                $dto->callerId,
                $dto->calleeId,
                $dto->callType
            );

            // Update external session ID
            $session->update(['external_session_id' => $externalSessionId]);

            return $session;
        });
    }

    public function end(EndCallDTO $dto): CallSession
    {
        return DB::transaction(function () use ($dto) {
            $session = CallSession::lockForUpdate()->findOrFail($dto->callSessionId);

            if ($session->caller_id !== $dto->callerId) {
                throw new BusinessException('Unauthorized to end this call.');
            }

            if (in_array($session->status, ['completed', 'missed', 'rejected', 'refunded'])) {
                throw new BusinessException('Call is already finalized.');
            }

            $ratePerMinute = $session->rate_per_minute;
            $durationSeconds = $dto->durationSeconds;

            // Bill for ceil minutes (e.g., 61 seconds = 2 minutes)
            $billableMinutes = (int) ceil($durationSeconds / 60);
            $actualCost = $billableMinutes * $ratePerMinute;

            // Ensure we don't charge more than the hold if duration exceeded hold somehow
            // A more complex system would have webhooks to end the call mid-stream
            $heldAmount = $ratePerMinute * self::MINIMUM_HOLD_MINUTES;
            if ($actualCost > $heldAmount) {
                $actualCost = (int) $heldAmount; // Cap at held amount for now
            }

            // Release the entire hold
            $ids = [$session->caller_id, $session->callee_id];
            sort($ids);
            $this->walletService->releaseHold(
                new HoldWalletDTO(
                    userId: $session->caller_id,
                    amount: (int) $heldAmount,
                    referenceId: $session->id,
                    referenceType: CallSession::class,
                    description: "Release hold for call {$session->id}"
                ),
                false
            );

            // Now deduct the actual cost
            if ($actualCost > 0) {
                $transactionType = $session->call_type === 'voice' 
                    ? TransactionType::VoiceCall 
                    : TransactionType::VideoCall;

                $this->walletService->deduct(new DeductWalletDTO(
                    userId: $session->caller_id,
                    amount: (int) $actualCost,
                    transactionType: $transactionType,
                    referenceId: $session->id,
                    referenceType: CallSession::class,
                    description: "Payment for {$session->call_type} call"
                ));
            }

            // Tell Stream to end the call
            if ($session->external_session_id) {
                $this->callProvider->endCall($session->external_session_id);
            }

            $session->update([
                'status' => 'completed',
                'ended_at' => now(),
                'duration_seconds' => $durationSeconds,
                'total_coins_charged' => $actualCost,
            ]);

            return $session;
        });
    }
}

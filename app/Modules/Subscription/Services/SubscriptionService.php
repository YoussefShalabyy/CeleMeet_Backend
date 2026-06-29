<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Services;

use App\Exceptions\BusinessException;
use App\Models\Subscription;
use App\Enums\TransactionType;
use App\Models\SubscriptionPlan;
use App\Modules\Subscription\DTOs\SubscribeDTO;
use App\Modules\Wallet\DTOs\DeductWalletDTO;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Support\Facades\DB;

final class SubscriptionService
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    public function subscribe(SubscribeDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            $plan = SubscriptionPlan::lockForUpdate()->find($dto->planId);

            if (!$plan || !$plan->is_active) {
                throw new BusinessException('Subscription plan is inactive or does not exist.');
            }

            if ($plan->creator_id === $dto->userId) {
                throw new BusinessException('You cannot subscribe to yourself.');
            }

            // Check if already subscribed
            $activeSubscription = Subscription::where('subscriber_id', $dto->userId)
                ->where('plan_id', $plan->id)
                ->active()
                ->first();

            if ($activeSubscription) {
                throw new BusinessException('You are already actively subscribed to this creator.');
            }

            // Deduct coins via wallet service
            if ($plan->coins > 0) {
                $this->walletService->deduct(new DeductWalletDTO(
                    userId: $dto->userId,
                    amount: $plan->coins,
                    transactionType: TransactionType::Subscription,
                    referenceId: $plan->id,
                    referenceType: SubscriptionPlan::class,
                    description: "Subscription to creator {$plan->creator_id}"
                ));
            }

            return Subscription::create([
                'plan_id'       => $plan->id,
                'creator_id'    => $plan->creator_id,
                'subscriber_id' => $dto->userId,
                'expires_at'    => now()->addDays($plan->duration_days),
                'auto_renew'    => false, // V1 does not support auto-renew
                'status'        => 'active',
            ]);
        });
    }

    public function isSubscribed(int $userId, int $creatorId): bool
    {
        return Subscription::where('subscriber_id', $userId)
            ->where('creator_id', $creatorId)
            ->active()
            ->exists();
    }
}

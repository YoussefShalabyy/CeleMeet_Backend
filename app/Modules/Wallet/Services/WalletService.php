<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Enums\TransactionStatus;
use App\Exceptions\BusinessException;
use App\Models\Wallet;
use App\Modules\Wallet\DTOs\CreditWalletDTO;
use App\Modules\Wallet\DTOs\DeductWalletDTO;
use App\Modules\Wallet\DTOs\HoldWalletDTO;
use Illuminate\Support\Facades\DB;

final class WalletService
{
    /**
     * Get the user's wallet.
     */
    public function getBalance(int $userId): Wallet
    {
        return Wallet::where('user_id', $userId)->firstOrFail();
    }

    /**
     * Credit coins to a user's wallet.
     */
    public function credit(CreditWalletDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $wallet = Wallet::where('user_id', $dto->userId)->lockForUpdate()->firstOrFail();

            $wallet->available_balance += $dto->amount;
            
            // Only increase total_earned if the transaction type conceptually means earnings (e.g., received a gift/message)
            // Recharges or refunds don't count towards lifetime earnings.
            if (! in_array($dto->transactionType->value, ['recharge', 'refund', 'admin_adjustment'], true)) {
                $wallet->total_earned += $dto->amount;
            }

            $wallet->save();

            $wallet->walletTransactions()->create([
                'user_id' => $dto->userId,
                'amount' => $dto->amount,
                'transaction_type' => $dto->transactionType,
                'status' => TransactionStatus::Completed,
                'reference_id' => $dto->referenceId,
                'reference_type' => $dto->referenceType,
                'description' => $dto->description,
                'metadata' => $dto->metadata,
            ]);
        });
    }

    /**
     * Deduct coins from a user's available balance.
     */
    public function deduct(DeductWalletDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $wallet = Wallet::where('user_id', $dto->userId)->lockForUpdate()->firstOrFail();

            if ($wallet->available_balance < $dto->amount) {
                throw new BusinessException('Insufficient balance.');
            }

            $wallet->available_balance -= $dto->amount;
            $wallet->total_spent += $dto->amount;
            $wallet->save();

            $wallet->walletTransactions()->create([
                'user_id' => $dto->userId,
                'amount' => $dto->amount,
                'transaction_type' => $dto->transactionType,
                'status' => TransactionStatus::Completed,
                'reference_id' => $dto->referenceId,
                'reference_type' => $dto->referenceType,
                'description' => $dto->description,
                'metadata' => $dto->metadata,
            ]);
        });
    }

    /**
     * Move coins from available_balance to held_balance.
     */
    public function hold(HoldWalletDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $wallet = Wallet::where('user_id', $dto->userId)->lockForUpdate()->firstOrFail();

            if ($wallet->available_balance < $dto->amount) {
                throw new BusinessException('Insufficient balance to hold.');
            }

            $wallet->available_balance -= $dto->amount;
            $wallet->held_balance += $dto->amount;
            $wallet->save();
            
            // Note: We typically don't create a wallet_transaction for a hold until it's finalized (deducted).
            // A hold is an internal ledger state, not a finalized movement.
        });
    }

    /**
     * Release held coins back to available_balance, or finalize as a deduction.
     */
    public function releaseHold(HoldWalletDTO $dto, bool $finalizeAsDeduction, \App\Enums\TransactionType $type = null): void
    {
        DB::transaction(function () use ($dto, $finalizeAsDeduction, $type): void {
            $wallet = Wallet::where('user_id', $dto->userId)->lockForUpdate()->firstOrFail();

            if ($wallet->held_balance < $dto->amount) {
                throw new BusinessException('Insufficient held balance to release.');
            }

            $wallet->held_balance -= $dto->amount;

            if ($finalizeAsDeduction) {
                // The coins are permanently gone.
                $wallet->total_spent += $dto->amount;
                
                if ($type === null) {
                    throw new \InvalidArgumentException('TransactionType must be provided when finalizing a hold as a deduction.');
                }

                $wallet->walletTransactions()->create([
                    'user_id' => $dto->userId,
                    'amount' => $dto->amount,
                    'transaction_type' => $type,
                    'status' => TransactionStatus::Completed,
                    'reference_id' => $dto->referenceId,
                    'reference_type' => $dto->referenceType,
                    'description' => $dto->description,
                ]);
            } else {
                // The coins are returned to the available balance.
                $wallet->available_balance += $dto->amount;
            }

            $wallet->save();
        });
    }
}

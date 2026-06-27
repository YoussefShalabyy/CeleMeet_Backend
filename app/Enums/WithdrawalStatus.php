<?php

declare(strict_types=1);

namespace App\Enums;

enum WithdrawalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid = 'paid';

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Paid withdrawals are immutable per business rules.
     * Once paid, the status cannot be changed.
     */
    public function isImmutable(): bool
    {
        return $this === self::Paid;
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Rejected, self::Paid => true,
            default => false,
        };
    }
}

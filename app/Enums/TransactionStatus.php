<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Reversed = 'reversed';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Failed, self::Reversed => true,
            self::Pending => false,
        };
    }
}

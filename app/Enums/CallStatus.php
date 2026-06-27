<?php

declare(strict_types=1);

namespace App\Enums;

enum CallStatus: string
{
    case Initiated = 'initiated';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Missed = 'missed';
    case Rejected = 'rejected';
    case Refunded = 'refunded';

    public function isActive(): bool
    {
        return match ($this) {
            self::Initiated, self::InProgress => true,
            default => false,
        };
    }

    public function isTerminal(): bool
    {
        return ! $this->isActive();
    }

    public function isBillable(): bool
    {
        return $this === self::Completed;
    }
}

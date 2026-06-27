<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function allowsPremiumAccess(): bool
    {
        return $this === self::Active;
    }
}

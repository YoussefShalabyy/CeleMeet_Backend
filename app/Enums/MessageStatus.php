<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageStatus: string
{
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function isRefundable(): bool
    {
        return match ($this) {
            self::Sent, self::Delivered => true,
            default => false,
        };
    }
}

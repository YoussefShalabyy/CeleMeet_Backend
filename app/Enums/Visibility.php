<?php

declare(strict_types=1);

namespace App\Enums;

enum Visibility: string
{
    case Free = 'free';
    case Premium = 'premium';
    case FollowersOnly = 'followers_only';

    public function requiresSubscription(): bool
    {
        return $this === self::Premium;
    }

    public function requiresFollow(): bool
    {
        return $this === self::FollowersOnly;
    }

    public function isPublic(): bool
    {
        return $this === self::Free;
    }
}

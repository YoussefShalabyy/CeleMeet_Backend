<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Regular = 'regular';
    case Celebrity = 'celebrity';
    case Admin = 'admin';
    case Moderator = 'moderator';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular User',
            self::Celebrity => 'Celebrity',
            self::Admin => 'Admin',
            self::Moderator => 'Moderator',
        };
    }

    public function isCreator(): bool
    {
        return $this === self::Celebrity;
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isModerator(): bool
    {
        return $this === self::Moderator || $this === self::Admin;
    }
}

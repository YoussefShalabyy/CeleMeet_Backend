<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Document = 'document';

    public function isPlayable(): bool
    {
        return match ($this) {
            self::Video, self::Audio => true,
            default => false,
        };
    }
}

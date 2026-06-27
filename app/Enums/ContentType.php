<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentType: string
{
    case Image = 'image';
    case Video = 'video';
    case Text = 'text';
}

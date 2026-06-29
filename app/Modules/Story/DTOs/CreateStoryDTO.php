<?php

declare(strict_types=1);

namespace App\Modules\Story\DTOs;

use App\Support\DTOs\BaseDTO;

final class CreateStoryDTO extends BaseDTO
{
    public function __construct(
        public readonly int $creatorId,
        public readonly int $mediaId,
        public readonly bool $isPremium,
    ) {}
}

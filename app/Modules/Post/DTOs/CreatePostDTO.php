<?php

declare(strict_types=1);

namespace App\Modules\Post\DTOs;

use App\Support\DTOs\BaseDTO;

final class CreatePostDTO extends BaseDTO
{
    /**
     * @param int[] $mediaIds
     */
    public function __construct(
        public readonly int $creatorId,
        public readonly string $contentType,
        public readonly ?string $caption,
        public readonly string $visibility,
        public readonly array $mediaIds = [],
    ) {}
}

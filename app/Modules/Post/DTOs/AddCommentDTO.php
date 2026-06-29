<?php

declare(strict_types=1);

namespace App\Modules\Post\DTOs;

use App\Support\DTOs\BaseDTO;

final class AddCommentDTO extends BaseDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $postId,
        public readonly string $body,
    ) {}
}

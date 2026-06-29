<?php

declare(strict_types=1);

namespace App\Modules\Post\DTOs;

use App\Support\DTOs\BaseDTO;

final class UpdatePostDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $caption,
        public readonly string $visibility,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Modules\Creator\DTOs;

use App\Support\DTOs\BaseDTO;

final class CreatorFilterDTO extends BaseDTO
{
    public function __construct(
        public readonly ?int $categoryId,
        public readonly ?string $searchTerm,
        public readonly string $sortBy,   // 'followers', 'newest'
        public readonly int $page,
        public readonly int $perPage,
    ) {}
}

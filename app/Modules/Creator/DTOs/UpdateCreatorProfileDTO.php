<?php

declare(strict_types=1);

namespace App\Modules\Creator\DTOs;

use App\Support\DTOs\BaseDTO;

final class UpdateCreatorProfileDTO extends BaseDTO
{
    public function __construct(
        public readonly string $displayName,
        public readonly ?string $bio,
        /** @var int[] */
        public readonly array $categoryIds,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Modules\User\DTOs;

use App\Support\DTOs\BaseDTO;

final class UpdateUserProfileDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $username,
    ) {}
}

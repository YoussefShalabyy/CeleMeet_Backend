<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTOs;

use App\Support\DTOs\BaseDTO;

final class LoginDTO extends BaseDTO
{
    public function __construct(
        public readonly string $identifier, // email or phone — resolved by AuthService
        public readonly string $password,
        public readonly ?string $deviceId,
    ) {}
}

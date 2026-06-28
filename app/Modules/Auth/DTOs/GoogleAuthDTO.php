<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTOs;

use App\Support\DTOs\BaseDTO;

final class GoogleAuthDTO extends BaseDTO
{
    public function __construct(
        public readonly string $googleIdToken,
        public readonly ?string $deviceId,
    ) {}
}

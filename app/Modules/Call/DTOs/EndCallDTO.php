<?php

declare(strict_types=1);

namespace App\Modules\Call\DTOs;

use App\Support\DTOs\BaseDTO;

final class EndCallDTO extends BaseDTO
{
    public function __construct(
        public readonly int $callerId,
        public readonly int $callSessionId,
        public readonly int $durationSeconds,
    ) {}
}

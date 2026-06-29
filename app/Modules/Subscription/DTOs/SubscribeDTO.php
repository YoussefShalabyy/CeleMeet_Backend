<?php

declare(strict_types=1);

namespace App\Modules\Subscription\DTOs;

use App\Support\DTOs\BaseDTO;

final class SubscribeDTO extends BaseDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $planId,
    ) {}
}

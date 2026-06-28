<?php

declare(strict_types=1);

namespace App\Modules\Wallet\DTOs;

use App\Support\DTOs\BaseDTO;

final class CreateCoinPackageDTO extends BaseDTO
{
    public function __construct(
        public readonly int $coins,
        public readonly float $price,
        public readonly string $currency,
        public readonly int $bonusCoins = 0,
        public readonly bool $isActive = true,
    ) {}
}

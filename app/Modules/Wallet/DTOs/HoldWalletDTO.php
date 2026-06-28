<?php

declare(strict_types=1);

namespace App\Modules\Wallet\DTOs;

use App\Support\DTOs\BaseDTO;

final class HoldWalletDTO extends BaseDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $amount,
        public readonly ?int $referenceId = null,
        public readonly ?string $referenceType = null,
        public readonly ?string $description = null,
    ) {
        if ($this->amount <= 0) {
            throw new \InvalidArgumentException('Hold amount must be positive.');
        }
    }
}

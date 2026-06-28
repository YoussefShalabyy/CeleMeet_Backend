<?php

declare(strict_types=1);

namespace App\Modules\Wallet\DTOs;

use App\Enums\TransactionType;
use App\Support\DTOs\BaseDTO;

final class CreditWalletDTO extends BaseDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $amount,
        public readonly TransactionType $transactionType,
        public readonly ?int $referenceId = null,
        public readonly ?string $referenceType = null,
        public readonly ?string $description = null,
        public readonly ?array $metadata = null,
    ) {
        if ($this->amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive.');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Resources;

use App\Http\Resources\BaseApiResource;

class WalletResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'available_balance' => $this->available_balance,
            'held_balance' => $this->held_balance,
            'total_earned' => $this->total_earned,
            'total_spent' => $this->total_spent,
        ];
    }
}

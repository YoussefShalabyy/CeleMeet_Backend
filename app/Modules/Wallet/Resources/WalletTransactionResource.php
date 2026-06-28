<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Resources;

use App\Http\Resources\BaseApiResource;

class WalletTransactionResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'transaction_type' => $this->transaction_type,
            'status' => $this->status,
            'reference_id' => $this->reference_id,
            'reference_type' => $this->reference_type,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}

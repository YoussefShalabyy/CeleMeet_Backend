<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Resources;

use App\Http\Resources\BaseApiResource;

class CoinPackageResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'coins' => $this->coins,
            'bonus_coins' => $this->bonus_coins,
            'price' => $this->price,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
        ];
    }
}

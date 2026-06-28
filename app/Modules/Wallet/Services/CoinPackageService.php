<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Models\CoinPackage;
use App\Modules\Wallet\DTOs\CreateCoinPackageDTO;

final class CoinPackageService
{
    public function create(CreateCoinPackageDTO $dto): CoinPackage
    {
        return CoinPackage::create([
            'coins' => $dto->coins,
            'price' => $dto->price,
            'currency' => $dto->currency,
            'bonus_coins' => $dto->bonusCoins,
            'is_active' => $dto->isActive,
        ]);
    }

    public function update(CoinPackage $package, array $data): CoinPackage
    {
        $package->update($data);
        return $package;
    }

    public function delete(CoinPackage $package): bool
    {
        return $package->delete();
    }
}

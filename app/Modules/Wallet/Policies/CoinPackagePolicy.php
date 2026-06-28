<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Policies;

use App\Models\CoinPackage;
use App\Models\User;

class CoinPackagePolicy
{
    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    public function view(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id;
    }
}

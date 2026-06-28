<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Every user gets a wallet immediately
        $user->wallet()->create([
            'available_balance' => 0,
            'held_balance'      => 0,
            'total_earned'      => 0,
            'total_spent'       => 0,
        ]);
    }
}

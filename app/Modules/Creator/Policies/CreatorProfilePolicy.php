<?php

declare(strict_types=1);

namespace App\Modules\Creator\Policies;

use App\Models\User;

class CreatorProfilePolicy
{
    /**
     * Only celebrity role can update a creator profile.
     */
    public function update(User $user): bool
    {
        return $user->role === 'celebrity';
    }
}

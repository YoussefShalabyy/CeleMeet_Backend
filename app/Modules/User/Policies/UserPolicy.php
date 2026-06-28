<?php

declare(strict_types=1);

namespace App\Modules\User\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Users can only update their own profile.
     */
    public function update(User $authUser, User $targetUser): bool
    {
        return $authUser->id === $targetUser->id;
    }
}

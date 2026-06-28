<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\DTOs\UpdateUserProfileDTO;

final class UserService
{
    public function getProfile(User $user): User
    {
        return $user->loadMissing('creatorProfile');
    }

    public function updateProfile(User $user, UpdateUserProfileDTO $dto): User
    {
        $data = array_filter([
            'username' => $dto->username,
        ], fn ($value) => $value !== null);

        if (! empty($data)) {
            $user->update($data);
        }

        return $user->refresh();
    }
}

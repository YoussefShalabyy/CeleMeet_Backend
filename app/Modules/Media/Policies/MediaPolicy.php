<?php

declare(strict_types=1);

namespace App\Modules\Media\Policies;

use App\Models\MediaAsset;
use App\Models\User;

class MediaPolicy
{
    /**
     * Users can only delete their own media assets.
     */
    public function delete(User $user, MediaAsset $mediaAsset): bool
    {
        return $mediaAsset->owner_type === User::class && $mediaAsset->owner_id === $user->id;
    }
}

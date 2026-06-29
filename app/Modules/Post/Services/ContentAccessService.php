<?php

declare(strict_types=1);

namespace App\Modules\Post\Services;

class ContentAccessService
{
    /**
     * Check if a user can view premium content from a specific creator.
     * 
     * In Phase 7, this is stubbed to return true only if the user is the creator.
     * In Phase 10 (Subscriptions), this will check the `subscriptions` table.
     */
    public function canViewPremium(int $userId, int $creatorId): bool
    {
        // Creators can always view their own premium content
        if ($userId === $creatorId) {
            return true;
        }

        // TODO: Phase 10 - Check if $userId has an active subscription to $creatorId
        return false;
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Story\Policies;

use App\Models\Story;
use App\Models\User;
use App\Modules\Post\Services\ContentAccessService;
use Illuminate\Auth\Access\HandlesAuthorization;

class StoryPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly ContentAccessService $accessService
    ) {}

    public function view(?User $user, Story $story): bool
    {
        if (!$story->is_premium) {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $this->accessService->canViewPremium($user->id, $story->creator_id);
    }

    public function delete(User $user, Story $story): bool
    {
        return $user->id === $story->creator_id;
    }
}

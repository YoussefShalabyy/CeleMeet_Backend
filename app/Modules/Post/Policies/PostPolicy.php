<?php

declare(strict_types=1);

namespace App\Modules\Post\Policies;

use App\Models\Post;
use App\Models\User;
use App\Modules\Post\Services\ContentAccessService;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly ContentAccessService $accessService
    ) {}

    public function view(?User $user, Post $post): bool
    {
        if ($post->visibility === 'free') {
            return true;
        }

        if (!$user) {
            return false; // Guests cannot view premium/followers_only
        }

        if ($post->visibility === 'premium') {
            return $this->accessService->canViewPremium($user->id, $post->creator_id);
        }

        // TODO: For 'followers_only', check follows table. We'll allow true for now.
        return true;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->creator_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->creator_id;
    }
}

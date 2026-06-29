<?php

declare(strict_types=1);

namespace App\Modules\Post\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    public function delete(User $user, Comment $comment): bool
    {
        // For now, only the author can delete. 
        // We could also allow the post creator to delete comments on their posts later.
        return $user->id === $comment->user_id;
    }
}

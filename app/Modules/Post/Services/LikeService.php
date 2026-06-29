<?php

declare(strict_types=1);

namespace App\Modules\Post\Services;

use App\Models\Like;
use App\Models\Post;

final class LikeService
{
    public function likePost(int $userId, int $postId): Like
    {
        // Use firstOrCreate to naturally handle duplicate attempts idempotently
        // without throwing exceptions or incrementing twice
        return Like::firstOrCreate([
            'user_id'       => $userId,
            'likeable_type' => Post::class,
            'likeable_id'   => $postId,
        ]);
    }

    public function unlikePost(int $userId, int $postId): void
    {
        $like = Like::where('user_id', $userId)
            ->where('likeable_type', Post::class)
            ->where('likeable_id', $postId)
            ->first();

        if ($like) {
            $like->delete();
        }
    }
}

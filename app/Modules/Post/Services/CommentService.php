<?php

declare(strict_types=1);

namespace App\Modules\Post\Services;

use App\Models\Comment;
use App\Modules\Post\DTOs\AddCommentDTO;
use Illuminate\Pagination\LengthAwarePaginator;

final class CommentService
{
    public function addComment(AddCommentDTO $dto): Comment
    {
        return Comment::create([
            'user_id' => $dto->userId,
            'post_id' => $dto->postId,
            'body'    => $dto->body,
        ]);
    }

    public function deleteComment(Comment $comment): void
    {
        $comment->delete();
    }

    public function getCommentsForPost(int $postId, int $perPage = 15): LengthAwarePaginator
    {
        return Comment::where('post_id', $postId)
            ->with(['user.creatorProfile', 'user.avatar']) // Preload typical user fields needed for display
            ->latest() // Standard for comments to show newest first, though oldest first is also valid.
            ->paginate($perPage);
    }
}

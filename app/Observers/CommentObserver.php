<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Comment;
use Illuminate\Support\Facades\DB;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        DB::table('posts')->where('id', $comment->post_id)->increment('comments_count');
    }

    public function deleted(Comment $comment): void
    {
        DB::table('posts')->where('id', $comment->post_id)->decrement('comments_count');
    }
}

<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class LikeObserver
{
    public function created(Like $like): void
    {
        if ($like->likeable_type === Post::class) {
            DB::table('posts')->where('id', $like->likeable_id)->increment('likes_count');
        }
    }

    public function deleted(Like $like): void
    {
        if ($like->likeable_type === Post::class) {
            DB::table('posts')->where('id', $like->likeable_id)->decrement('likes_count');
        }
    }
}

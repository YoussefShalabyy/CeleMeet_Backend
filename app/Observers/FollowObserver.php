<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CreatorProfile;
use App\Models\Follow;
use Illuminate\Support\Facades\DB;

class FollowObserver
{
    /**
     * Atomically increment the creator's followers_count when a follow record is created.
     * Uses DB::increment to prevent race conditions — never direct assignment.
     */
    public function created(Follow $follow): void
    {
        DB::table('creator_profiles')
            ->where('user_id', $follow->creator_id)
            ->increment('followers_count');
    }

    /**
     * Atomically decrement the creator's followers_count when a follow record is deleted.
     * Uses DB::decrement to prevent race conditions — never direct assignment.
     * Only decrements if count > 0 to prevent it going negative.
     */
    public function deleted(Follow $follow): void
    {
        DB::table('creator_profiles')
            ->where('user_id', $follow->creator_id)
            ->where('followers_count', '>', 0)
            ->decrement('followers_count');
    }
}

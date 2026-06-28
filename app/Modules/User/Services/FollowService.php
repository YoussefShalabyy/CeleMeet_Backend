<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException;
use App\Models\CreatorProfile;
use App\Models\Follow;
use App\Modules\User\DTOs\FollowCreatorDTO;
use Illuminate\Pagination\LengthAwarePaginator;

final class FollowService
{
    /**
     * Follow a creator.
     * Uses firstOrCreate for idempotency — following an already-followed creator is a no-op.
     *
     * @throws BusinessException  If the user tries to follow themselves.
     * @throws NotFoundException  If the creator profile does not exist.
     */
    public function follow(FollowCreatorDTO $dto): void
    {
        if ($dto->followerId === $dto->creatorId) {
            throw new BusinessException('You cannot follow yourself.');
        }

        $creatorExists = CreatorProfile::where('user_id', $dto->creatorId)->exists();
        if (! $creatorExists) {
            throw new NotFoundException('Creator not found.');
        }

        // firstOrCreate is idempotent: if follow already exists, it returns it without inserting.
        // The observer only fires on `created`, so the counter won't double-increment.
        Follow::firstOrCreate([
            'follower_id' => $dto->followerId,
            'creator_id'  => $dto->creatorId,
        ]);
    }

    /**
     * Unfollow a creator.
     * Safe to call even if the user is not following the creator — it is a no-op.
     */
    public function unfollow(FollowCreatorDTO $dto): void
    {
        // Find and delete fires the 'deleted' observer event correctly.
        $follow = Follow::where('follower_id', $dto->followerId)
            ->where('creator_id', $dto->creatorId)
            ->first();

        if ($follow) {
            $follow->delete();
        }
    }

    /**
     * Get a paginated list of creator profiles that the given user follows.
     */
    public function getFollowing(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return CreatorProfile::whereHas('followers', fn ($query) => $query->where('follower_id', $userId))
            ->with(['categories', 'user', 'avatar'])
            ->paginate($perPage);
    }

    /**
     * Check whether a user is following a specific creator.
     */
    public function isFollowing(int $followerId, int $creatorId): bool
    {
        return Follow::where('follower_id', $followerId)
            ->where('creator_id', $creatorId)
            ->exists();
    }
}

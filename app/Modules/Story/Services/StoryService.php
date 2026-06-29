<?php

declare(strict_types=1);

namespace App\Modules\Story\Services;

use App\Exceptions\BusinessException;
use App\Models\MediaAsset;
use App\Models\Story;
use App\Models\User;
use App\Modules\Post\Services\ContentAccessService;
use App\Modules\Story\DTOs\CreateStoryDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class StoryService
{
    public function __construct(
        private readonly ContentAccessService $accessService
    ) {}

    public function createStory(CreateStoryDTO $dto): Story
    {
        return DB::transaction(function () use ($dto) {
            // Verify media ownership
            $media = MediaAsset::where('id', $dto->mediaId)
                ->where('owner_id', $dto->creatorId)
                ->where('owner_type', User::class)
                ->first();

            if (!$media) {
                throw new BusinessException('Media asset is invalid or not owned by you.');
            }

            $story = Story::create([
                'creator_id' => $dto->creatorId,
                'media_id'   => $dto->mediaId,
                'is_premium' => $dto->isPremium,
                'expires_at' => now()->addHours(24),
            ]);

            // Morph media ownership to the Story
            $media->update([
                'owner_type' => Story::class,
                'owner_id'   => $story->id,
            ]);

            return $story->load('media', 'creator.user');
        });
    }

    public function deleteStory(Story $story): void
    {
        $story->delete();
    }

    public function getActiveStories(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        // Get IDs of creators the user follows
        $followingIds = DB::table('follows')
            ->where('follower_id', $userId)
            ->pluck('creator_id')
            ->toArray();

        // Also include the user's own stories if they are a creator
        $followingIds[] = $userId;

        $paginator = Story::whereIn('creator_id', $followingIds)
            ->where('expires_at', '>', now())
            ->with(['creator.user', 'creator.avatar', 'media'])
            ->orderBy('creator_id') // Groups stories by creator
            ->oldest() // Within each creator, show oldest active first
            ->paginate($perPage);

        return $this->filterPremiumContent($paginator, $userId);
    }

    /**
     * Hides premium stories if the user does not have access.
     */
    private function filterPremiumContent(LengthAwarePaginator $paginator, ?int $userId): LengthAwarePaginator
    {
        $paginator->getCollection()->transform(function (Story $story) use ($userId) {
            if ($story->is_premium) {
                $hasAccess = $userId && $this->accessService->canViewPremium($userId, $story->creator_id);
                if (!$hasAccess) {
                    $story->setRelation('media', null);
                    $story->is_censored = true;
                }
            }
            return $story;
        });

        return $paginator;
    }
}

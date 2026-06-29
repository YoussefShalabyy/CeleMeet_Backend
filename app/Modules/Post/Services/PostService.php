<?php

declare(strict_types=1);

namespace App\Modules\Post\Services;

use App\Exceptions\BusinessException;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\User;
use App\Modules\Post\DTOs\CreatePostDTO;
use App\Modules\Post\DTOs\UpdatePostDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PostService
{
    public function __construct(
        private readonly ContentAccessService $accessService
    ) {}

    public function createPost(CreatePostDTO $dto): Post
    {
        return DB::transaction(function () use ($dto) {
            $post = Post::create([
                'creator_id'   => $dto->creatorId,
                'content_type' => $dto->contentType,
                'caption'      => $dto->caption,
                'visibility'   => $dto->visibility,
            ]);

            DB::table('creator_profiles')->where('user_id', $dto->creatorId)->increment('posts_count');

            if (!empty($dto->mediaIds)) {
                // Ensure media belongs to the user and is not already assigned
                $mediaAssets = MediaAsset::whereIn('id', $dto->mediaIds)
                    ->where('owner_id', $dto->creatorId)
                    ->where('owner_type', User::class)
                    ->get();

                if ($mediaAssets->count() !== count($dto->mediaIds)) {
                    throw new BusinessException('One or more media assets are invalid or not owned by you.');
                }

                // Morphs the media to belong to the new Post
                MediaAsset::whereIn('id', $dto->mediaIds)->update([
                    'owner_type' => Post::class,
                    'owner_id'   => $post->id,
                ]);
            }

            return $post->load('media', 'creator.user');
        });
    }

    public function updatePost(Post $post, UpdatePostDTO $dto): Post
    {
        $post->update([
            'caption'    => $dto->caption,
            'visibility' => $dto->visibility,
        ]);

        return $post;
    }

    public function deletePost(Post $post): void
    {
        DB::transaction(function () use ($post) {
            $post->delete();
            DB::table('creator_profiles')->where('user_id', $post->creator_id)->decrement('posts_count');
        });
    }

    public function getPaginatedFeed(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        // Get IDs of creators the user follows
        $followingIds = DB::table('follows')
            ->where('follower_id', $userId)
            ->pluck('creator_id')
            ->toArray();

        // Also include the user's own posts if they are a creator
        $followingIds[] = $userId;

        $paginator = Post::whereIn('creator_id', $followingIds)
            ->where('is_active', true)
            ->with(['creator.user', 'creator.avatar', 'media'])
            ->latest()
            ->paginate($perPage);

        return $this->filterPremiumContent($paginator, $userId);
    }

    public function getCreatorPosts(int $creatorId, ?int $userId, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = Post::where('creator_id', $creatorId)
            ->where('is_active', true)
            ->with(['creator.user', 'creator.avatar', 'media'])
            ->latest()
            ->paginate($perPage);

        return $this->filterPremiumContent($paginator, $userId);
    }

    /**
     * Hides premium posts if the user does not have access.
     */
    private function filterPremiumContent(LengthAwarePaginator $paginator, ?int $userId): LengthAwarePaginator
    {
        $paginator->getCollection()->transform(function (Post $post) use ($userId) {
            if ($post->visibility === 'premium') {
                $hasAccess = $userId && $this->accessService->canViewPremium($userId, $post->creator_id);
                if (!$hasAccess) {
                    // Censor the post content
                    $post->caption = null;
                    $post->setRelation('media', collect([])); // Hide media
                    $post->is_censored = true; // Flag for frontend
                }
            }
            return $post;
        });

        return $paginator;
    }
}

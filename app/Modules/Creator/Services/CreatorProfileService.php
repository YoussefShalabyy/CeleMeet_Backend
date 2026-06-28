<?php

declare(strict_types=1);

namespace App\Modules\Creator\Services;

use App\Exceptions\NotFoundException;
use App\Models\Category;
use App\Models\CreatorProfile;
use App\Models\User;
use App\Modules\Creator\DTOs\CreatorFilterDTO;
use App\Modules\Creator\DTOs\UpdateCreatorProfileDTO;
use Illuminate\Pagination\LengthAwarePaginator;

final class CreatorProfileService
{
    /**
     * Get a creator's public profile by user ID.
     *
     * @throws NotFoundException if the user has no creator profile
     */
    public function getPublicProfile(int $userId): CreatorProfile
    {
        $profile = CreatorProfile::with(['user', 'avatar', 'cover', 'categories'])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (! $profile) {
            throw new NotFoundException('Creator not found.');
        }

        return $profile;
    }

    /**
     * List creators with filtering, search, and pagination.
     */
    public function listCreators(CreatorFilterDTO $dto): LengthAwarePaginator
    {
        $query = CreatorProfile::with(['user', 'avatar', 'categories'])
            ->where('is_active', true);

        if ($dto->categoryId) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $dto->categoryId));
        }

        if ($dto->searchTerm) {
            $query->where('display_name', 'like', '%' . $dto->searchTerm . '%');
        }

        $query->when(
            $dto->sortBy === 'followers',
            fn ($q) => $q->orderByDesc('followers_count'),
            fn ($q) => $q->orderByDesc('created_at'),
        );

        return $query->paginate($dto->perPage, page: $dto->page);
    }

    /**
     * Create or update the creator profile for a user (upsert pattern).
     */
    public function updateProfile(User $user, UpdateCreatorProfileDTO $dto): CreatorProfile
    {
        $profile = CreatorProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $dto->displayName,
                'bio'          => $dto->bio,
            ]
        );

        // Sync categories — replaces any previous selections
        if (! empty($dto->categoryIds)) {
            $profile->categories()->sync($dto->categoryIds);
        }

        return $profile->load(['user', 'avatar', 'cover', 'categories']);
    }
}

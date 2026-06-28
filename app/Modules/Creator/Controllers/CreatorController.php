<?php

declare(strict_types=1);

namespace App\Modules\Creator\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Modules\Creator\DTOs\CreatorFilterDTO;
use App\Modules\Creator\DTOs\UpdateCreatorProfileDTO;
use App\Modules\Creator\Requests\ListCreatorsRequest;
use App\Modules\Creator\Requests\UpdateCreatorProfileRequest;
use App\Modules\Creator\Resources\CreatorProfileListResource;
use App\Modules\Creator\Resources\CreatorProfileResource;
use App\Modules\Creator\Services\CategoryService;
use App\Modules\Creator\Services\CreatorProfileService;
use App\Modules\Creator\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;

class CreatorController extends BaseApiController
{
    public function __construct(
        private readonly CreatorProfileService $creatorProfileService,
        private readonly CategoryService $categoryService,
    ) {}

    /**
     * List creators — public endpoint, no auth required.
     */
    public function index(ListCreatorsRequest $request): JsonResponse
    {
        $paginator = $this->creatorProfileService->listCreators(new CreatorFilterDTO(
            categoryId:  $request->integer('category_id') ?: null,
            searchTerm:  $request->string('search')->toString() ?: null,
            sortBy:      $request->input('sort_by', 'newest'),
            page:        $request->integer('page', 1),
            perPage:     $request->integer('per_page', 15),
        ));

        return ApiResponse::paginated(
            paginator: $paginator,
            data:      CreatorProfileListResource::collection($paginator),
            message:   'Creators retrieved successfully.',
        );
    }

    /**
     * Get a single creator's public profile.
     */
    public function show(int $id): JsonResponse
    {
        $profile = $this->creatorProfileService->getPublicProfile($id);

        return ApiResponse::success(
            data:    new CreatorProfileResource($profile),
            message: 'Creator profile retrieved successfully.',
        );
    }

    /**
     * Update the authenticated celebrity's own creator profile.
     */
    public function update(UpdateCreatorProfileRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $this->authorize('update', \App\Models\CreatorProfile::class);

        $profile = $this->creatorProfileService->updateProfile($user, new UpdateCreatorProfileDTO(
            displayName: $request->validated('display_name'),
            bio:         $request->validated('bio'),
            categoryIds: $request->validated('category_ids', []),
        ));

        return ApiResponse::success(
            data:    new CreatorProfileResource($profile),
            message: 'Creator profile updated successfully.',
        );
    }

    /**
     * List all categories — public endpoint.
     */
    public function categories(): JsonResponse
    {
        $categories = $this->categoryService->listAll();

        return ApiResponse::success(
            data:    CategoryResource::collection($categories),
            message: 'Categories retrieved successfully.',
        );
    }
}

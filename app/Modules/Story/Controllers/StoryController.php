<?php

declare(strict_types=1);

namespace App\Modules\Story\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\Story;
use App\Models\User;
use App\Modules\Story\DTOs\CreateStoryDTO;
use App\Modules\Story\Requests\CreateStoryRequest;
use App\Modules\Story\Resources\StoryResource;
use App\Modules\Story\Services\StoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoryController extends BaseApiController
{
    public function __construct(
        private readonly StoryService $storyService
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        
        $perPage = (int) $request->query('per_page', 15);
        $stories = $this->storyService->getActiveStories($user->id, $perPage);

        return ApiResponse::paginated(
            paginator: $stories,
            data: StoryResource::collection($stories),
            message: 'Stories retrieved successfully.'
        );
    }

    public function store(CreateStoryRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if ($user->role !== 'celebrity') {
            throw new BusinessException('Only creators can create stories.');
        }

        $story = $this->storyService->createStory(new CreateStoryDTO(
            creatorId: $user->id,
            mediaId:   (int) $request->validated('media_id'),
            isPremium: (bool) $request->validated('is_premium', false),
        ));

        return ApiResponse::created(
            data: new StoryResource($story),
            message: 'Story created successfully.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $story = Story::with(['creator.user', 'creator.avatar', 'media'])->findOrFail($id);

        /** @var User|null $user */
        $user = auth('api')->user();
        
        $this->authorize('view', $story);

        return ApiResponse::success(
            data: new StoryResource($story),
            message: 'Story retrieved successfully.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $story = Story::findOrFail($id);
        $this->authorize('delete', $story);

        $this->storyService->deleteStory($story);

        return ApiResponse::success(message: 'Story deleted successfully.');
    }
}

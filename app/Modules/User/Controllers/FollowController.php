<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Modules\Creator\Resources\CreatorProfileListResource;
use App\Modules\User\DTOs\FollowCreatorDTO;
use App\Modules\User\Services\FollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends BaseApiController
{
    public function __construct(
        private readonly FollowService $followService
    ) {}

    public function follow(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $this->followService->follow(new FollowCreatorDTO(
            followerId: $user->id,
            creatorId: $id,
        ));

        return ApiResponse::success(message: 'Creator followed successfully.');
    }

    public function unfollow(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $this->followService->unfollow(new FollowCreatorDTO(
            followerId: $user->id,
            creatorId: $id,
        ));

        return ApiResponse::success(message: 'Creator unfollowed successfully.');
    }

    public function following(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $perPage = (int) $request->query('per_page', 15);
        $following = $this->followService->getFollowing($user->id, $perPage);

        return ApiResponse::paginated(
            paginator: $following,
            data: CreatorProfileListResource::collection($following),
            message: 'Following list retrieved.'
        );
    }
}

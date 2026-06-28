<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Modules\User\DTOs\UpdateUserProfileDTO;
use App\Modules\User\Requests\UpdateUserProfileRequest;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends BaseApiController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Get the authenticated user's own profile.
     */
    public function me(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        return ApiResponse::success(
            data:    new UserResource($this->userService->getProfile($user)),
            message: 'Profile retrieved successfully.',
        );
    }

    /**
     * Update the authenticated user's own profile.
     */
    public function update(UpdateUserProfileRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $this->authorize('update', $user);

        $updated = $this->userService->updateProfile($user, new UpdateUserProfileDTO(
            username: $request->validated('username'),
        ));

        return ApiResponse::success(
            data:    new UserResource($updated),
            message: 'Profile updated successfully.',
        );
    }
}

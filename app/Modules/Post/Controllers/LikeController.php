<?php

declare(strict_types=1);

namespace App\Modules\Post\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\Post;
use App\Models\User;
use App\Modules\Post\Services\LikeService;
use Illuminate\Http\JsonResponse;

class LikeController extends BaseApiController
{
    public function __construct(
        private readonly LikeService $likeService
    ) {}

    public function store(int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        
        /** @var User $user */
        $user = auth('api')->user();
        
        // Ensure user has access to view the post before liking it
        $this->authorize('view', $post);

        $this->likeService->likePost($user->id, $post->id);

        return ApiResponse::success(message: 'Post liked successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        
        /** @var User $user */
        $user = auth('api')->user();

        $this->likeService->unlikePost($user->id, $post->id);

        return ApiResponse::success(message: 'Post unliked successfully.');
    }
}

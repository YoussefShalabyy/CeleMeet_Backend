<?php

declare(strict_types=1);

namespace App\Modules\Post\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\Post;
use App\Models\User;
use App\Modules\Post\DTOs\CreatePostDTO;
use App\Modules\Post\DTOs\UpdatePostDTO;
use App\Modules\Post\Requests\CreatePostRequest;
use App\Modules\Post\Requests\UpdatePostRequest;
use App\Modules\Post\Resources\PostResource;
use App\Modules\Post\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends BaseApiController
{
    public function __construct(
        private readonly PostService $postService
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        
        $perPage = (int) $request->query('per_page', 15);
        $feed = $this->postService->getPaginatedFeed($user->id, $perPage);

        return ApiResponse::paginated(
            paginator: $feed,
            data: PostResource::collection($feed),
            message: 'Feed retrieved successfully.'
        );
    }

    public function store(CreatePostRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if ($user->role !== 'celebrity') {
            throw new BusinessException('Only creators can create posts.');
        }

        $post = $this->postService->createPost(new CreatePostDTO(
            creatorId:   $user->id,
            contentType: $request->validated('content_type'),
            caption:     $request->validated('caption'),
            visibility:  $request->validated('visibility'),
            mediaIds:    $request->validated('media_ids', []),
        ));

        return ApiResponse::created(
            data: new PostResource($post),
            message: 'Post created successfully.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $post = Post::with(['creator.user', 'creator.avatar', 'media'])->findOrFail($id);

        /** @var User|null $user */
        $user = auth('api')->user(); // Might be null if guest, handle in policy
        
        $this->authorize('view', $post); // Policy checks view access based on visibility

        return ApiResponse::success(
            data: new PostResource($post),
            message: 'Post retrieved successfully.'
        );
    }

    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $this->authorize('update', $post);

        $post = $this->postService->updatePost($post, new UpdatePostDTO(
            caption:    $request->validated('caption'),
            visibility: $request->validated('visibility'),
        ));

        return ApiResponse::success(
            data: new PostResource($post->load(['creator.user', 'media'])),
            message: 'Post updated successfully.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $this->authorize('delete', $post);

        $this->postService->deletePost($post);

        return ApiResponse::success(message: 'Post deleted successfully.');
    }

    public function creatorPosts(Request $request, int $creatorId): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();
        
        $perPage = (int) $request->query('per_page', 15);
        $posts = $this->postService->getCreatorPosts($creatorId, $user?->id, $perPage);

        return ApiResponse::paginated(
            paginator: $posts,
            data: PostResource::collection($posts),
            message: 'Creator posts retrieved successfully.'
        );
    }
}

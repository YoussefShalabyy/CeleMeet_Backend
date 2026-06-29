<?php

declare(strict_types=1);

namespace App\Modules\Post\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Modules\Post\DTOs\AddCommentDTO;
use App\Modules\Post\Requests\AddCommentRequest;
use App\Modules\Post\Resources\CommentResource;
use App\Modules\Post\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends BaseApiController
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    public function index(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $this->authorize('view', $post);

        $perPage = (int) $request->query('per_page', 15);
        $comments = $this->commentService->getCommentsForPost($post->id, $perPage);

        return ApiResponse::paginated(
            paginator: $comments,
            data: CommentResource::collection($comments),
            message: 'Comments retrieved successfully.'
        );
    }

    public function store(AddCommentRequest $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        
        /** @var User $user */
        $user = auth('api')->user();

        // Must be able to view the post to comment on it
        $this->authorize('view', $post);

        $comment = $this->commentService->addComment(new AddCommentDTO(
            userId: $user->id,
            postId: $post->id,
            body:   $request->validated('body')
        ));

        return ApiResponse::created(
            data: new CommentResource($comment->load('user.creatorProfile', 'user.avatar')),
            message: 'Comment added successfully.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);
        $this->authorize('delete', $comment);

        $this->commentService->deleteComment($comment);

        return ApiResponse::success(message: 'Comment deleted successfully.');
    }
}

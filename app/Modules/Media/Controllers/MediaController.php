<?php

declare(strict_types=1);

namespace App\Modules\Media\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\MediaAsset;
use App\Models\User;
use App\Modules\Media\DTOs\UploadMediaDTO;
use App\Modules\Media\Requests\UploadMediaRequest;
use App\Modules\Media\Resources\MediaAssetResource;
use App\Modules\Media\Services\MediaService;
use Illuminate\Http\JsonResponse;

class MediaController extends BaseApiController
{
    public function __construct(
        private readonly MediaService $mediaService
    ) {}

    public function upload(UploadMediaRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $mediaAsset = $this->mediaService->upload(new UploadMediaDTO(
            file:       $request->file('file'),
            collection: $request->validated('collection'),
            ownerId:    $user->id,
            ownerType:  User::class,
        ));

        return ApiResponse::success(
            data:    new MediaAssetResource($mediaAsset),
            message: 'Media uploaded successfully.',
            code:    201,
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $mediaAsset = MediaAsset::findOrFail($id);

        $this->authorize('delete', $mediaAsset);

        $this->mediaService->delete($mediaAsset);

        return ApiResponse::success(
            message: 'Media deleted successfully.',
        );
    }
}

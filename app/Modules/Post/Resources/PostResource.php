<?php

declare(strict_types=1);

namespace App\Modules\Post\Resources;

use App\Http\Resources\BaseApiResource;
use App\Modules\Creator\Resources\CreatorProfileResource;
use App\Modules\Media\Resources\MediaAssetResource;

class PostResource extends BaseApiResource
{
    public function toArray($request): array
    {
        // If the post is censored (user has no access to premium content)
        if ($this->is_censored) {
            return [
                'id'             => $this->id,
                'creator_id'     => $this->creator_id,
                'visibility'     => $this->visibility,
                'is_locked'      => true, // Frontend flag to show lock icon
                'creator'        => new CreatorProfileResource($this->whenLoaded('creator')),
                'created_at'     => $this->created_at,
            ];
        }

        return [
            'id'             => $this->id,
            'creator_id'     => $this->creator_id,
            'content_type'   => $this->content_type,
            'caption'        => $this->caption,
            'visibility'     => $this->visibility,
            'likes_count'    => $this->likes_count,
            'comments_count' => $this->comments_count,
            'media'          => MediaAssetResource::collection($this->whenLoaded('media')),
            'creator'        => new CreatorProfileResource($this->whenLoaded('creator')),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}

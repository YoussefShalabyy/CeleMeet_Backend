<?php

declare(strict_types=1);

namespace App\Modules\Story\Resources;

use App\Http\Resources\BaseApiResource;
use App\Modules\Creator\Resources\CreatorProfileResource;
use App\Modules\Media\Resources\MediaAssetResource;

class StoryResource extends BaseApiResource
{
    public function toArray($request): array
    {
        if ($this->is_censored) {
            return [
                'id'         => $this->id,
                'creator_id' => $this->creator_id,
                'is_premium' => true,
                'is_locked'  => true,
                'creator'    => new CreatorProfileResource($this->whenLoaded('creator')),
                'expires_at' => $this->expires_at,
                'created_at' => $this->created_at,
            ];
        }

        return [
            'id'         => $this->id,
            'creator_id' => $this->creator_id,
            'is_premium' => $this->is_premium,
            'media'      => new MediaAssetResource($this->whenLoaded('media')),
            'creator'    => new CreatorProfileResource($this->whenLoaded('creator')),
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }
}

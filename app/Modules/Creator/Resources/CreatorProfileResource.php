<?php

declare(strict_types=1);

namespace App\Modules\Creator\Resources;

use App\Http\Resources\BaseApiResource;

class CreatorProfileResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id'                        => $this->user_id,
            'display_name'              => $this->display_name,
            'bio'                       => $this->bio,
            'avatar_url'                => $this->avatar?->url,
            'cover_url'                 => $this->cover?->url,
            'verification_badge'        => $this->verification_badge,
            'followers_count'           => $this->followers_count,
            'posts_count'               => $this->posts_count,
            'premium_subscribers_count' => $this->premium_subscribers_count,
            'is_active'                 => $this->is_active,
            'categories'                => CategoryResource::collection($this->whenLoaded('categories')),
            'user'                      => $this->when($this->relationLoaded('user'), fn () => [
                'id'       => $this->user->id,
                'uuid'     => $this->user->uuid,
                'username' => $this->user->username,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Creator\Resources;

use App\Http\Resources\BaseApiResource;

class CreatorProfileListResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->user_id,
            'display_name'       => $this->display_name,
            'avatar_url'         => $this->avatar?->url,
            'verification_badge' => $this->verification_badge,
            'followers_count'    => $this->followers_count,
            'categories'         => CategoryResource::collection($this->whenLoaded('categories')),
            'user'               => $this->when($this->relationLoaded('user'), fn () => [
                'username' => $this->user->username,
            ]),
        ];
    }
}

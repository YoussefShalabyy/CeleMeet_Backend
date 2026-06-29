<?php

declare(strict_types=1);

namespace App\Modules\Post\Resources;

use App\Http\Resources\BaseApiResource;

class CommentResource extends BaseApiResource
{
    public function toArray($request): array
    {
        $userData = null;
        if ($this->relationLoaded('user') && $this->user) {
            $avatar = $this->user->relationLoaded('avatar') && $this->user->avatar 
                ? $this->user->avatar->url 
                : null;

            $displayName = $this->user->relationLoaded('creatorProfile') && $this->user->creatorProfile
                ? $this->user->creatorProfile->display_name
                : 'User'; // Fallback

            $userData = [
                'id'           => $this->user->id,
                'display_name' => $displayName,
                'avatar_url'   => $avatar,
            ];
        }

        return [
            'id'         => $this->id,
            'post_id'    => $this->post_id,
            'user'       => $userData,
            'body'       => $this->body,
            'created_at' => $this->created_at,
        ];
    }
}

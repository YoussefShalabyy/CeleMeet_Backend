<?php

declare(strict_types=1);

namespace App\Modules\User\Resources;

use App\Http\Resources\BaseApiResource;

class UserResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'uuid'       => $this->uuid,
            'username'   => $this->username,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'role'       => $this->role,
            'status'     => $this->status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

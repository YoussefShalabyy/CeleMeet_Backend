<?php

declare(strict_types=1);

namespace App\Modules\Auth\Resources;

use App\Http\Resources\BaseApiResource;

class AuthUserResource extends BaseApiResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'username'     => $this->username,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'role'         => $this->role,
            'status'       => $this->status,
            'access_token' => $this->additional['access_token'] ?? null,
            'token_type'   => $this->additional['token_type'] ?? 'bearer',
            'expires_in'   => $this->additional['expires_in'] ?? null,
        ];
    }
}

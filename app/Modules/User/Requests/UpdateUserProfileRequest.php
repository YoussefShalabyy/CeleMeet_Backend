<?php

declare(strict_types=1);

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy check done in controller via Gate
    }

    public function rules(): array
    {
        $userId = auth('api')->id();

        return [
            'username' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                "unique:users,username,{$userId}",
            ],
        ];
    }
}

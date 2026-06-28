<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:150'],
            'password'   => ['required', 'string', 'min:8'],
            'username'   => ['nullable', 'string', 'max:50', 'unique:users,username', 'alpha_dash'],
            'device_id'  => ['nullable', 'string', 'max:255'],
        ];
    }
}

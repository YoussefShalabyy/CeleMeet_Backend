<?php

declare(strict_types=1);

namespace App\Modules\Post\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'caption'    => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', 'string', 'in:free,premium,followers_only'],
        ];
    }
}

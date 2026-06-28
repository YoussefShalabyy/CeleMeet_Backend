<?php

declare(strict_types=1);

namespace App\Modules\Creator\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreatorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'display_name'   => ['required', 'string', 'max:100'],
            'bio'            => ['nullable', 'string', 'max:1000'],
            'category_ids'   => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ];
    }
}

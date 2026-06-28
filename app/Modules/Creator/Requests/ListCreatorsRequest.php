<?php

declare(strict_types=1);

namespace App\Modules\Creator\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListCreatorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'  => ['nullable', 'integer', 'exists:categories,id'],
            'search'       => ['nullable', 'string', 'max:100'],
            'sort_by'      => ['nullable', 'string', 'in:followers,newest'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}

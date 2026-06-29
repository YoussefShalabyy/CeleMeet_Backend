<?php

declare(strict_types=1);

namespace App\Modules\Story\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'media_id'   => ['required', 'integer', 'exists:media_assets,id'],
            'is_premium' => ['boolean'],
        ];
    }
}

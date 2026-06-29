<?php

declare(strict_types=1);

namespace App\Modules\Post\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_type' => ['required', 'string', 'in:image,video,text'],
            'caption'      => ['nullable', 'string', 'max:2000'],
            'visibility'   => ['required', 'string', 'in:free,premium,followers_only'],
            'media_ids'    => ['nullable', 'array', 'max:10'],
            'media_ids.*'  => ['integer', 'exists:media_assets,id'],
        ];
    }
}

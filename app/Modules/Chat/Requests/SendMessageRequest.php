<?php

declare(strict_types=1);

namespace App\Modules\Chat\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'integer', 'exists:creator_profiles,user_id'],
            'content'     => ['required', 'string', 'max:5000'],
        ];
    }
}

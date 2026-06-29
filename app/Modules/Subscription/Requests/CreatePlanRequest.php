<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'         => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:500'],
            'coins'         => ['required', 'integer', 'min:1'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}

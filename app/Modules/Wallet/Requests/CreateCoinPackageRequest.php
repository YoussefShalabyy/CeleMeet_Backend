<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCoinPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via policy in controller
    }

    public function rules(): array
    {
        return [
            'coins'       => ['required', 'integer', 'min:1'],
            'price'       => ['required', 'numeric', 'min:0.01'],
            'currency'    => ['required', 'string', 'size:3'],
            'bonus_coins' => ['sometimes', 'integer', 'min:0'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}

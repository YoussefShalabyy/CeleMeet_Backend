<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoinPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via policy in controller
    }

    public function rules(): array
    {
        return [
            'coins'       => ['sometimes', 'integer', 'min:1'],
            'price'       => ['sometimes', 'numeric', 'min:0.01'],
            'currency'    => ['sometimes', 'string', 'size:3'],
            'bonus_coins' => ['sometimes', 'integer', 'min:0'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}

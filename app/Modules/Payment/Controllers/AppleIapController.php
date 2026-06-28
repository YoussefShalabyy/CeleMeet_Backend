<?php

declare(strict_types=1);

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppleIapController extends BaseApiController
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'receipt_data' => 'required|string'
        ]);

        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $this->paymentService->verifyAppleReceipt($user, $request->input('receipt_data'));

        return ApiResponse::success(
            message: 'Receipt verified and coins credited successfully.'
        );
    }
}

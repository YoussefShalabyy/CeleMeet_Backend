<?php

declare(strict_types=1);

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymobController extends BaseApiController
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'coin_package_id' => 'required|integer|exists:coin_packages,id'
        ]);

        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $checkoutUrl = $this->paymentService->initiatePaymobPayment($user, (int) $request->input('coin_package_id'));

        return ApiResponse::success(
            data: ['checkout_url' => $checkoutUrl],
            message: 'Payment initiated successfully.'
        );
    }

    public function webhook(Request $request): JsonResponse
    {
        // Paymob sends the HMAC signature in the 'hmac' query parameter
        $signature = $request->query('hmac', '');

        $this->paymentService->handlePaymobWebhook($request->all(), $signature);

        return response()->json(['status' => 'success']);
    }
}

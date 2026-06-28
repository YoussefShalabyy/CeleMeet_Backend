<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Modules\Wallet\Resources\WalletResource;
use App\Modules\Wallet\Resources\WalletTransactionResource;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\JsonResponse;

class WalletController extends BaseApiController
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        
        $wallet = $this->walletService->getBalance($user->id);

        $this->authorize('view', $wallet);

        return ApiResponse::success(
            data: new WalletResource($wallet),
        );
    }

    public function transactions(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        $wallet = $this->walletService->getBalance($user->id);
        
        $this->authorize('view', $wallet);

        $transactions = $wallet->walletTransactions()->latest()->paginate(15);

        return ApiResponse::success(
            data: WalletTransactionResource::collection($transactions),
        );
    }
}

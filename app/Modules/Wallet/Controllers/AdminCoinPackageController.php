<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\CoinPackage;
use App\Modules\Wallet\DTOs\CreateCoinPackageDTO;
use App\Modules\Wallet\Requests\CreateCoinPackageRequest;
use App\Modules\Wallet\Requests\UpdateCoinPackageRequest;
use App\Modules\Wallet\Resources\CoinPackageResource;
use App\Modules\Wallet\Services\CoinPackageService;
use Illuminate\Http\JsonResponse;

class AdminCoinPackageController extends BaseApiController
{
    public function __construct(
        private readonly CoinPackageService $coinPackageService
    ) {}

    public function store(CreateCoinPackageRequest $request): JsonResponse
    {
        $this->authorize('manage', CoinPackage::class);

        $dto = new CreateCoinPackageDTO(
            coins: (int) $request->validated('coins'),
            price: (float) $request->validated('price'),
            currency: $request->validated('currency'),
            bonusCoins: (int) ($request->validated('bonus_coins') ?? 0),
            isActive: (bool) ($request->validated('is_active') ?? true),
        );

        $package = $this->coinPackageService->create($dto);

        return ApiResponse::success(
            data: new CoinPackageResource($package),
            message: 'Coin package created.',
            code: 201,
        );
    }

    public function update(UpdateCoinPackageRequest $request, int $id): JsonResponse
    {
        $this->authorize('manage', CoinPackage::class);

        $package = CoinPackage::findOrFail($id);

        $package = $this->coinPackageService->update($package, $request->validated());

        return ApiResponse::success(
            data: new CoinPackageResource($package),
            message: 'Coin package updated.',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('manage', CoinPackage::class);

        $package = CoinPackage::findOrFail($id);

        $this->coinPackageService->delete($package);

        return ApiResponse::success(
            message: 'Coin package deleted.',
        );
    }
}

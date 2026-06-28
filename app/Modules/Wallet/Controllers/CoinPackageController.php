<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\CoinPackage;
use App\Modules\Wallet\Resources\CoinPackageResource;
use Illuminate\Http\JsonResponse;

class CoinPackageController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $packages = CoinPackage::where('is_active', true)->get();

        return ApiResponse::success(
            data: CoinPackageResource::collection($packages),
        );
    }
}

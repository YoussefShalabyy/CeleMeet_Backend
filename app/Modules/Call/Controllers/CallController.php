<?php

declare(strict_types=1);

namespace App\Modules\Call\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\CallSession;
use App\Models\User;
use App\Modules\Call\DTOs\EndCallDTO;
use App\Modules\Call\DTOs\InitiateCallDTO;
use App\Modules\Call\Services\CallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends BaseApiController
{
    public function __construct(
        private readonly CallService $callService
    ) {}

    public function getToken(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        $token = $this->callService->generateToken($user);

        return response()->json([
            'success' => true,
            'token' => $token,
            'apiKey' => config('stream.api_key'),
            'user' => [
                'id' => (string) $user->id,
            ],
        ]);
    }

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'callee_id' => ['required', 'integer', 'exists:creator_profiles,user_id'],
            'call_type' => ['required', 'string', 'in:voice,video'],
        ]);

        /** @var User $user */
        $user = auth('api')->user();

        $callSession = $this->callService->initiate(new InitiateCallDTO(
            callerId: $user->id,
            calleeId: (int) $validated['callee_id'],
            callType: $validated['call_type'],
        ));

        return ApiResponse::created(
            data: $callSession->toArray(),
            message: 'Call initiated successfully.'
        );
    }

    public function end(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'duration_seconds' => ['required', 'integer', 'min:0'],
        ]);

        /** @var User $user */
        $user = auth('api')->user();

        $callSession = $this->callService->end(new EndCallDTO(
            callerId: $user->id,
            callSessionId: $id,
            durationSeconds: (int) $validated['duration_seconds'],
        ));

        return ApiResponse::success(
            data: $callSession->toArray(),
            message: 'Call ended and billed successfully.'
        );
    }
}

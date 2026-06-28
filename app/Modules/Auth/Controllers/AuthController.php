<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Modules\Auth\DTOs\GoogleAuthDTO;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Requests\GoogleAuthRequest;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Resources\AuthUserResource;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends BaseApiController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Register a new user — returns token immediately.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(new RegisterDTO(
            identifier: $request->validated('identifier'),
            password:   $request->validated('password'),
            username:   $request->validated('username'),
            deviceId:   $request->validated('device_id'),
        ));

        $resource = (new AuthUserResource($result['user']))->additional([
            'access_token' => $result['access_token'],
            'token_type'   => $result['token_type'],
            'expires_in'   => $result['expires_in'],
        ]);

        return ApiResponse::success(
            data:    $resource,
            message: 'Account created successfully.',
            code:    201,
        );
    }

    /**
     * Login with identifier (email or phone) + password.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(new LoginDTO(
            identifier: $request->validated('identifier'),
            password:   $request->validated('password'),
            deviceId:   $request->validated('device_id'),
        ));

        $resource = (new AuthUserResource($result['user']))->additional([
            'access_token' => $result['access_token'],
            'token_type'   => $result['token_type'],
            'expires_in'   => $result['expires_in'],
        ]);

        return ApiResponse::success(
            data:    $resource,
            message: 'Logged in successfully.',
        );
    }

    /**
     * Google Sign-In — login or register in one call.
     */
    public function google(GoogleAuthRequest $request): JsonResponse
    {
        $result = $this->authService->googleAuth(new GoogleAuthDTO(
            googleIdToken: $request->validated('google_id_token'),
            deviceId:      $request->validated('device_id'),
        ));

        $resource = (new AuthUserResource($result['user']))->additional([
            'access_token' => $result['access_token'],
            'token_type'   => $result['token_type'],
            'expires_in'   => $result['expires_in'],
        ]);

        return ApiResponse::success(
            data:    $resource,
            message: 'Authenticated with Google successfully.',
        );
    }

    /**
     * Logout — invalidate the current JWT token.
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return ApiResponse::success(message: 'Logged out successfully.');
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return ApiResponse::success(
            data:    new AuthUserResource($user),
            message: 'User retrieved successfully.',
        );
    }
}

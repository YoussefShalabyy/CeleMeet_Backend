<?php

declare(strict_types=1);

use App\Modules\Auth\Controllers\AuthController;

Route::prefix('v1')->group(function (): void {

    // ─── Phase 1: Auth ──────────────────────────────────────────────────
    Route::prefix('auth')->group(function (): void {
        // Guest endpoints
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);
        Route::post('google',   [AuthController::class, 'google']);

        // Authenticated endpoints
        Route::middleware('auth:api')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);
        });
    });


    // ─── Phase 2: Users & Creators ──────────────────────────────────────

    // User routes (authenticated)
    Route::prefix('users')->middleware('auth:api')->group(function (): void {
        Route::get('me',  [\App\Modules\User\Controllers\UserController::class, 'me']);
        Route::put('me',  [\App\Modules\User\Controllers\UserController::class, 'update']);
    });

    // Creator routes — public listing + authenticated profile update
    Route::prefix('creators')->group(function (): void {
        Route::get('/',    [\App\Modules\Creator\Controllers\CreatorController::class, 'index']);
        Route::get('{id}', [\App\Modules\Creator\Controllers\CreatorController::class, 'show'])->whereNumber('id');
    });

    Route::get('categories', [\App\Modules\Creator\Controllers\CreatorController::class, 'categories']);

    Route::middleware('auth:api')->prefix('creator')->group(function (): void {
        Route::put('profile', [\App\Modules\Creator\Controllers\CreatorController::class, 'update']);
    });


    // ─── Phase 3: Media ─────────────────────────────────────────────────
    Route::prefix('media')->middleware('auth:api')->group(function (): void {
        Route::post('upload', [\App\Modules\Media\Controllers\MediaController::class, 'upload']);
        Route::delete('{id}', [\App\Modules\Media\Controllers\MediaController::class, 'destroy'])->whereNumber('id');
    });

    // ─── Phase 4: Wallet ────────────────────────────────────────────────
    Route::middleware('auth:api')->group(function (): void {
        Route::prefix('wallet')->group(function (): void {
            Route::get('/', [\App\Modules\Wallet\Controllers\WalletController::class, 'show']);
            Route::get('transactions', [\App\Modules\Wallet\Controllers\WalletController::class, 'transactions']);
        });

        Route::prefix('coin-packages')->group(function (): void {
            Route::get('/', [\App\Modules\Wallet\Controllers\CoinPackageController::class, 'index']);
        });

        Route::prefix('admin/coin-packages')->group(function (): void {
            Route::post('/', [\App\Modules\Wallet\Controllers\AdminCoinPackageController::class, 'store']);
            Route::put('{id}', [\App\Modules\Wallet\Controllers\AdminCoinPackageController::class, 'update'])->whereNumber('id');
            Route::delete('{id}', [\App\Modules\Wallet\Controllers\AdminCoinPackageController::class, 'destroy'])->whereNumber('id');
        });
    });

    // ─── Phase 5: Payments ──────────────────────────────────────────────
    // Route::prefix('payments')->group(base_path('routes/api/payments.php'));

    // ─── Phase 7: Posts ─────────────────────────────────────────────────
    // Route::prefix('posts')->group(base_path('routes/api/posts.php'));

    // ─── Phase 8: Stories ───────────────────────────────────────────────
    // Route::prefix('stories')->group(base_path('routes/api/stories.php'));

    // ─── Phase 10: Subscriptions ────────────────────────────────────────
    // Route::prefix('subscriptions')->group(base_path('routes/api/subscriptions.php'));

    // ─── Phase 11: Messaging ────────────────────────────────────────────
    // Route::prefix('messages')->group(base_path('routes/api/messages.php'));

    // ─── Phase 12: Calls ────────────────────────────────────────────────
    // Route::prefix('calls')->group(base_path('routes/api/calls.php'));

    // ─── Phase 13: Notifications ────────────────────────────────────────
    // Route::prefix('notifications')->group(base_path('routes/api/notifications.php'));

    // ─── Phase 14: Earnings & Withdrawals ───────────────────────────────
    // Route::prefix('creator')->group(base_path('routes/api/creator.php'));

    // ─── Phase 15: Admin ────────────────────────────────────────────────
    // Route::prefix('admin')->group(base_path('routes/api/admin.php'));

});

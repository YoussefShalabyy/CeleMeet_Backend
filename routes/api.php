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
    Route::middleware('auth:api')->group(function (): void {
        Route::post('payments/paymob/initiate', [\App\Modules\Payment\Controllers\PaymobController::class, 'initiate']);
        Route::post('payments/apple/verify', [\App\Modules\Payment\Controllers\AppleIapController::class, 'verify']);
    });
    
    // Webhook must be public so Paymob servers can reach it
    Route::post('payments/paymob/webhook', [\App\Modules\Payment\Controllers\PaymobController::class, 'webhook']);

    // ─── Phase 6: Follow System ──────────────────────────────────────────
    Route::middleware('auth:api')->group(function (): void {
        // Follow / unfollow a creator
        Route::post('creators/{id}/follow',    [\App\Modules\User\Controllers\FollowController::class, 'follow'])->whereNumber('id');
        Route::delete('creators/{id}/follow',  [\App\Modules\User\Controllers\FollowController::class, 'unfollow'])->whereNumber('id');
        // List who the authenticated user follows
        Route::get('users/me/following', [\App\Modules\User\Controllers\FollowController::class, 'following']);
    });

    // ─── Phase 7 & 9: Posts & Engagement ───────────────────────────────
    Route::get('creators/{id}/posts', [\App\Modules\Post\Controllers\PostController::class, 'creatorPosts'])->whereNumber('id');
    
    Route::middleware('auth:api')->group(function (): void {
        Route::get('posts', [\App\Modules\Post\Controllers\PostController::class, 'index']);
        Route::post('posts', [\App\Modules\Post\Controllers\PostController::class, 'store']);
        Route::get('posts/{id}', [\App\Modules\Post\Controllers\PostController::class, 'show'])->whereNumber('id');
        Route::put('posts/{id}', [\App\Modules\Post\Controllers\PostController::class, 'update'])->whereNumber('id');
        Route::delete('posts/{id}', [\App\Modules\Post\Controllers\PostController::class, 'destroy'])->whereNumber('id');

        Route::post('posts/{id}/like', [\App\Modules\Post\Controllers\LikeController::class, 'store'])->whereNumber('id');
        Route::delete('posts/{id}/like', [\App\Modules\Post\Controllers\LikeController::class, 'destroy'])->whereNumber('id');

        Route::get('posts/{id}/comments', [\App\Modules\Post\Controllers\CommentController::class, 'index'])->whereNumber('id');
        Route::post('posts/{id}/comments', [\App\Modules\Post\Controllers\CommentController::class, 'store'])->whereNumber('id');
        Route::delete('comments/{id}', [\App\Modules\Post\Controllers\CommentController::class, 'destroy'])->whereNumber('id');
    });

    // ─── Phase 8: Stories ───────────────────────────────────────────────
    Route::middleware('auth:api')->group(function (): void {
        Route::get('stories', [\App\Modules\Story\Controllers\StoryController::class, 'index']);
        Route::post('stories', [\App\Modules\Story\Controllers\StoryController::class, 'store']);
        Route::get('stories/{id}', [\App\Modules\Story\Controllers\StoryController::class, 'show'])->whereNumber('id');
        Route::delete('stories/{id}', [\App\Modules\Story\Controllers\StoryController::class, 'destroy'])->whereNumber('id');
    });

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

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are versioned under /api/v1/.
|
| Module routes are registered here as the project grows.
| Each phase adds its own routes in this file, grouped by module.
|
| Naming convention: kebab-case paths, e.g. /creator-profiles, /coin-packages
|
*/

Route::prefix('v1')->group(function (): void {

    // ─── Phase 1: Auth ──────────────────────────────────────────────────
    // Route::prefix('auth')->group(base_path('routes/api/auth.php'));

    // ─── Phase 2: Users & Creators ──────────────────────────────────────
    // Route::prefix('users')->group(base_path('routes/api/users.php'));
    // Route::prefix('creators')->group(base_path('routes/api/creators.php'));

    // ─── Phase 3: Media ─────────────────────────────────────────────────
    // Route::prefix('media')->group(base_path('routes/api/media.php'));

    // ─── Phase 4: Wallet ────────────────────────────────────────────────
    // Route::prefix('wallet')->group(base_path('routes/api/wallet.php'));

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

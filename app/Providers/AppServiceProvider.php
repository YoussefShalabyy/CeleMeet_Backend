<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Configures Eloquent to enforce best practices:
     * - preventLazyLoading: Forces all relationships to be eager-loaded in dev.
     *   This eliminates N+1 queries before they reach production.
     * - preventSilentlyDiscardingAttributes: Throws if you try to fill a non-fillable attribute.
     *   Catches mass-assignment bugs immediately.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
    }
}

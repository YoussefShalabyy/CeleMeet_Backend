<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\CreatorProfile;
use App\Models\User;
use App\Modules\Creator\Policies\CreatorProfilePolicy;
use App\Modules\User\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(CreatorProfile::class, CreatorProfilePolicy::class);
        Gate::policy(\App\Models\MediaAsset::class, \App\Modules\Media\Policies\MediaPolicy::class);
    }
}

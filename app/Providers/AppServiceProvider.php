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

        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\Follow::observe(\App\Observers\FollowObserver::class);
        \App\Models\Like::observe(\App\Observers\LikeObserver::class);
        \App\Models\Comment::observe(\App\Observers\CommentObserver::class);

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(CreatorProfile::class, CreatorProfilePolicy::class);
        Gate::policy(\App\Models\MediaAsset::class, \App\Modules\Media\Policies\MediaPolicy::class);
        Gate::policy(\App\Models\Wallet::class, \App\Modules\Wallet\Policies\WalletPolicy::class);
        Gate::policy(\App\Models\CoinPackage::class, \App\Modules\Wallet\Policies\CoinPackagePolicy::class);
        Gate::policy(\App\Models\Post::class, \App\Modules\Post\Policies\PostPolicy::class);
        Gate::policy(\App\Models\Story::class, \App\Modules\Story\Policies\StoryPolicy::class);
        Gate::policy(\App\Models\Comment::class, \App\Modules\Post\Policies\CommentPolicy::class);
        Gate::policy(\App\Models\SubscriptionPlan::class, \App\Modules\Subscription\Policies\SubscriptionPolicy::class);
    }
}

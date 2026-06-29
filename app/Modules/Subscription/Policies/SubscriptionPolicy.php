<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Policies;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    public function subscribe(User $user, SubscriptionPlan $plan): bool
    {
        return $user->id !== $plan->creator_id;
    }
}

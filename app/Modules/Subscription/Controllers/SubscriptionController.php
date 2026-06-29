<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\CreatorProfile;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Modules\Subscription\DTOs\SubscribeDTO;
use App\Modules\Subscription\Requests\CreatePlanRequest;
use App\Modules\Subscription\Requests\SubscribeRequest;
use App\Modules\Subscription\Resources\SubscriptionPlanResource;
use App\Modules\Subscription\Resources\SubscriptionResource;
use App\Modules\Subscription\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends BaseApiController
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    public function getCreatorPlan(int $creatorId): JsonResponse
    {
        $plan = SubscriptionPlan::where('creator_id', $creatorId)
            ->where('is_active', true)
            ->first();

        if (!$plan) {
            throw new BusinessException('This creator does not have an active subscription plan.');
        }

        return ApiResponse::success(
            data: new SubscriptionPlanResource($plan),
            message: 'Subscription plan retrieved successfully.'
        );
    }

    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        $plan = SubscriptionPlan::findOrFail($request->validated('plan_id'));
        $this->authorize('subscribe', $plan);

        $subscription = $this->subscriptionService->subscribe(new SubscribeDTO(
            userId: $user->id,
            planId: $plan->id
        ));

        return ApiResponse::created(
            data: new SubscriptionResource($subscription->load('creator', 'plan')),
            message: 'Subscribed successfully.'
        );
    }

    public function mySubscriptions(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        $subscriptions = Subscription::where('subscriber_id', $user->id)
            ->active()
            ->with('creator', 'plan')
            ->get();

        return ApiResponse::success(
            data: SubscriptionResource::collection($subscriptions),
            message: 'Subscriptions retrieved successfully.'
        );
    }

    public function cancel(int $id): JsonResponse
    {
        $subscription = Subscription::where('subscriber_id', auth('api')->id())
            ->findOrFail($id);

        $subscription->update(['status' => 'cancelled']);

        return ApiResponse::success(message: 'Subscription cancelled successfully.');
    }

    public function updateCreatorPlan(CreatePlanRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if ($user->role !== 'celebrity') {
            throw new BusinessException('Only creators can configure subscription plans.');
        }

        $plan = SubscriptionPlan::updateOrCreate(
            ['creator_id' => $user->id],
            [
                'title'         => $request->validated('title'),
                'description'   => $request->validated('description'),
                'coins'         => $request->validated('coins'),
                'duration_days' => $request->validated('duration_days'),
                'is_active'     => true,
            ]
        );

        return ApiResponse::success(
            data: new SubscriptionPlanResource($plan),
            message: 'Subscription plan updated successfully.'
        );
    }
}

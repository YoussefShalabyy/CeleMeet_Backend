<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Resources;

use App\Http\Resources\BaseApiResource;
use App\Modules\Creator\Resources\CreatorProfileResource;

class SubscriptionResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'creator'    => new CreatorProfileResource($this->whenLoaded('creator')),
            'plan'       => new SubscriptionPlanResource($this->whenLoaded('plan')),
            'started_at' => $this->started_at,
            'expires_at' => $this->expires_at,
            'status'     => $this->status,
        ];
    }
}

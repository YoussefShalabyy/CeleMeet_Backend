<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Resources;

use App\Http\Resources\BaseApiResource;

class SubscriptionPlanResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'creator_id'    => $this->creator_id,
            'title'         => $this->title,
            'description'   => $this->description,
            'coins'         => $this->coins,
            'duration_days' => $this->duration_days,
            'is_active'     => $this->is_active,
        ];
    }
}

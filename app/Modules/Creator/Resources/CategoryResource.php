<?php

declare(strict_types=1);

namespace App\Modules\Creator\Resources;

use App\Http\Resources\BaseApiResource;

class CategoryResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }
}

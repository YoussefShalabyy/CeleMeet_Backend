<?php

declare(strict_types=1);

namespace App\Modules\Media\Resources;

use App\Http\Resources\BaseApiResource;

class MediaAssetResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this->id,
            'url'       => $this->url,
            'mime_type' => $this->mime_type,
            'width'     => $this->width,
            'height'    => $this->height,
            'duration'  => $this->duration,
        ];
    }
}

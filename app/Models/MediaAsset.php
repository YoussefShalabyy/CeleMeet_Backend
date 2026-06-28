<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'owner_id',
        'owner_type',
        'collection',
        'provider',
        'provider_id',
        'url',
        'mime_type',
        'size',
        'width',
        'height',
        'duration',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size'     => 'integer',
            'width'    => 'integer',
            'height'   => 'integer',
            'duration' => 'integer',
        ];
    }
}

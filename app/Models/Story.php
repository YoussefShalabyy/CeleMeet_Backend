<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Story extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'creator_id',
        'media_id',
        'is_premium',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class, 'creator_id', 'user_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id', 'id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'creator_id',
        'content_type',
        'caption',
        'visibility',
        'likes_count',
        'comments_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'likes_count'    => 'integer',
            'comments_count' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class, 'creator_id', 'user_id');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(MediaAsset::class, 'owner');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}

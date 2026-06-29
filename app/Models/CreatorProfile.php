<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreatorProfile extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'display_name',
        'bio',
        'avatar_media_id',
        'cover_media_id',
        'verification_badge',
        'followers_count',
        'posts_count',
        'premium_subscribers_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'verification_badge'         => 'boolean',
            'is_active'                  => 'boolean',
            'followers_count'            => 'integer',
            'posts_count'                => 'integer',
            'premium_subscribers_count'  => 'integer',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'avatar_media_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_media_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'creator_categories',
            'creator_id',
            'category_id'
        );
    }

    public function followers(): HasMany
    {
        return $this->hasMany(Follow::class, 'creator_id', 'user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'creator_id', 'user_id');
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class, 'creator_id', 'user_id');
    }
}

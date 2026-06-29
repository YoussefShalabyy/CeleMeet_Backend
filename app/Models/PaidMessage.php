<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaidMessage extends Model
{
    use HasFactory;

    public $timestamps = false; // We only have created_at handled manually or by DB

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'external_channel_id',
        'external_message_id',
        'message_type',
        'content',
        'media_asset_id',
        'price_in_coins',
        'status',
        'refund_eligible_until',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'refund_eligible_until' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = $model->freshTimestamp();
            }
        });
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class, 'receiver_id', 'user_id');
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }
}

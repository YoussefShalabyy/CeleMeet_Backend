<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageRefund extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'paid_message_id',
        'user_id',
        'coins_returned',
        'reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->processed_at)) {
                $model->processed_at = $model->freshTimestamp();
            }
        });
    }

    public function paidMessage(): BelongsTo
    {
        return $this->belongsTo(PaidMessage::class, 'paid_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

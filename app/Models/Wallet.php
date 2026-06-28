<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'available_balance',
        'held_balance',
        'total_earned',
        'total_spent',
    ];

    protected function casts(): array
    {
        return [
            'available_balance' => 'integer',
            'held_balance'      => 'integer',
            'total_earned'      => 'integer',
            'total_spent'       => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

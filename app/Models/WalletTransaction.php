<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    public const UPDATED_AT = null; // Immutable ledger

    protected $fillable = [
        'wallet_id',
        'user_id',
        'amount',
        'transaction_type',
        'status',
        'reference_id',
        'reference_type',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'transaction_type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'metadata' => 'array',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

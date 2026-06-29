<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorService extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'service_type',
        'price_in_coins',
        'is_enabled',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'is_enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class, 'creator_id', 'user_id');
    }
}

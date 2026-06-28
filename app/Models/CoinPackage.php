<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinPackage extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'coins',
        'bonus_coins',
        'price',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'coins' => 'integer',
            'bonus_coins' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}

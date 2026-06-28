<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CoinPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoinPackageFactory extends Factory
{
    protected $model = CoinPackage::class;

    public function definition(): array
    {
        return [
            'coins' => $this->faker->numberBetween(100, 1000),
            'bonus_coins' => $this->faker->numberBetween(0, 100),
            'price' => $this->faker->randomFloat(2, 0.99, 99.99),
            'currency' => 'USD',
            'is_active' => true,
        ];
    }
}

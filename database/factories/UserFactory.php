<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'uuid'     => Str::uuid()->toString(),
            'email'    => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'username' => fake()->unique()->userName(),
            'role'     => 'regular',
            'status'   => 'active',
            'is_banned' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'admin']);
    }

    public function celebrity(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'celebrity']);
    }

    public function banned(): static
    {
        return $this->state(fn (array $attributes) => ['is_banned' => true]);
    }
}

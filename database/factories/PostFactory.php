<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id'   => null, // Provided by test
            'content_type' => 'text',
            'caption'      => $this->faker->paragraph(),
            'visibility'   => 'free',
            'is_active'    => true,
        ];
    }
}

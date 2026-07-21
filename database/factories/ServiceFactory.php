<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'             => fake()->unique()->words(3, true),
            'description'      => fake()->optional()->sentence(),
            'default_price_ht' => fake()->randomFloat(2, 50, 800),
            'is_active'        => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}

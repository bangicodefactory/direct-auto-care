<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id'    => User::factory(),
            'type'       => fake()->randomElement(StockMovementType::cases())->value,
            'quantity'   => fake()->numberBetween(1, 50),
            'reason'     => fake()->optional(0.7)->sentence(),
            'invoice_id' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category'            => fake()->randomElement(ProductCategory::cases())->value,
            'name'                => fake()->words(3, true),
            'sku'                 => fake()->unique()->bothify('SKU-####-??'),
            'unit'                => fake()->randomElement(['L', 'unité', 'paire']),
            'price_buy'           => fake()->randomFloat(2, 10, 500),
            'price_sell'          => fake()->randomFloat(2, 20, 800),
            'quantity_on_hand'    => fake()->numberBetween(0, 100),
            'low_stock_threshold' => fake()->numberBetween(2, 10),
        ];
    }
}

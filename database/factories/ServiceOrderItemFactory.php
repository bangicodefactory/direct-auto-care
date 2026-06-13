<?php

namespace Database\Factories;

use App\Models\ServiceOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceOrderItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 4);
        $unit     = fake()->randomFloat(2, 50, 800);

        return [
            'service_order_id' => ServiceOrder::factory(),
            'product_id'       => null,
            'description'      => fake()->randomElement([
                "Main d'œuvre",
                'Filtre à huile',
                'Plaquettes de frein',
                'Vidange huile moteur',
                'Batterie',
                'Diagnostic électronique',
            ]),
            'quantity'         => $quantity,
            'unit_price_ht'    => $unit,
            'line_total_ht'    => round($quantity * $unit, 2),
        ];
    }
}

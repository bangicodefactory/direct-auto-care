<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity      = fake()->randomFloat(2, 1, 10);
        $unitPriceHt   = fake()->randomFloat(2, 20, 500);
        $lineTotalHt   = round($quantity * $unitPriceHt, 2);

        return [
            'invoice_id'    => Invoice::factory(),
            'product_id'    => null,
            'description'   => fake()->sentence(3),
            'quantity'      => $quantity,
            'unit_price_ht' => $unitPriceHt,
            'line_total_ht' => $lineTotalHt,
        ];
    }
}

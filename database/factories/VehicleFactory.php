<?php

namespace Database\Factories;

use App\Enums\FuelType;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected static array $makes = [
        'Dacia', 'Renault', 'Peugeot', 'Volkswagen', 'Toyota',
        'Hyundai', 'Kia', 'Ford', 'Citroën', 'Fiat',
    ];

    protected static array $models = [
        'Logan', 'Clio', 'Sandero', 'Duster', '208',
        'Golf', 'Corolla', 'Tucson', 'Sportage', 'Focus',
    ];

    public function definition(): array
    {
        $letter = fake()->randomElement(['A', 'B', 'W', 'H', 'D']);
        $digits = str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT);
        $region = fake()->numerify('##');

        return [
            'customer_id'   => Customer::factory(),
            'make'          => fake()->randomElement(self::$makes),
            'model'         => fake()->randomElement(self::$models),
            'year'          => fake()->numberBetween(2005, 2024),
            'license_plate' => "{$digits}-{$letter}-{$region}",
            'vin'           => null,
            'mileage'       => fake()->numberBetween(0, 300_000),
            'fuel_type'     => fake()->randomElement(FuelType::cases())->value,
            'color'         => fake()->optional(0.8)->randomElement([
                'Blanc', 'Noir', 'Gris', 'Argent', 'Rouge', 'Bleu', 'Vert',
            ]),
        ];
    }
}

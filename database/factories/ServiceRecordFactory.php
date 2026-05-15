<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id'         => Vehicle::factory(),
            'mileage_at_service' => fake()->numberBetween(0, 300_000),
            'notes'              => fake()->optional(0.7)->sentence(),
            'serviced_at'        => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
        ];
    }
}

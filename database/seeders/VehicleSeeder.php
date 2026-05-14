<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        Customer::all()->each(function (Customer $customer) {
            Vehicle::factory(fake()->numberBetween(2, 3))->create([
                'customer_id' => $customer->id,
            ]);
        });
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles + service catalog + admin (the production baseline)...
        $this->call(ProductionSeeder::class);

        // ...then the demo transactional data (local/showcase only).
        $this->call(CustomerSeeder::class);
        $this->call(VehicleSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(InvoiceSeeder::class);
        $this->call(AppointmentSeeder::class);
        $this->call(ReminderSeeder::class);
        $this->call(ServiceOrderSeeder::class);
    }
}

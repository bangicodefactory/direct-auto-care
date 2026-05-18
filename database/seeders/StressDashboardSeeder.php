<?php

namespace Database\Seeders;

use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Generates ~1 000 customers and ~3 000 invoices to stress-test the dashboard.
 * Run after migrate:fresh --seed to layer on top of the default seeded data:
 *   php artisan db:seed --class=StressDashboardSeeder
 */
class StressDashboardSeeder extends Seeder
{
    private const CUSTOMER_COUNT = 1_000;
    private const INVOICE_COUNT  = 3_000;

    // Weighted status distribution — mirrors realistic garage data.
    private const STATUS_WEIGHTS = [
        'paid'      => 50,
        'issued'    => 25,
        'draft'     => 15,
        'cancelled' => 10,
    ];

    public function run(): void
    {
        $admin = User::role('admin')->firstOrFail();

        $this->command->info('Creating ' . self::CUSTOMER_COUNT . ' customers...');
        Customer::factory(self::CUSTOMER_COUNT)->create();

        $this->command->info('Creating vehicles...');
        Customer::query()->each(function (Customer $customer) {
            Vehicle::factory(fake()->numberBetween(1, 2))->create([
                'customer_id' => $customer->id,
            ]);
        });

        $this->command->info('Creating ' . self::INVOICE_COUNT . ' invoices...');
        $customerIds = Customer::pluck('id');
        $statusPool  = $this->buildStatusPool();

        foreach (range(1, self::INVOICE_COUNT) as $i) {
            $customerId = $customerIds->random();
            $vehicle    = Vehicle::where('customer_id', $customerId)->inRandomOrder()->first();

            Invoice::factory()->create([
                'customer_id' => $customerId,
                'vehicle_id'  => $vehicle?->id,
                'created_by'  => $admin->id,
                'status'      => fake()->randomElement($statusPool),
                'type'        => InvoiceType::Invoice->value,
            ]);

            if ($i % 500 === 0) {
                $this->command->info("  {$i} / " . self::INVOICE_COUNT);
            }
        }

        $this->command->info('Done.');
    }

    /** @return string[] */
    private function buildStatusPool(): array
    {
        $pool = [];
        foreach (self::STATUS_WEIGHTS as $status => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $status;
            }
        }

        return $pool;
    }
}

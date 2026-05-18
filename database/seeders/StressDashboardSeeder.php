<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
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
 *
 * Idempotency: not guaranteed — re-run migrate:fresh --seed before repeating.
 */
class StressDashboardSeeder extends Seeder
{
    private const CUSTOMER_COUNT = 1_000;
    private const INVOICE_COUNT  = 3_000;

    // Weighted status distribution — mirrors realistic garage data.
    private const STATUS_WEIGHTS = [
        InvoiceStatus::Paid->value      => 50,
        InvoiceStatus::Issued->value    => 25,
        InvoiceStatus::Draft->value     => 15,
        InvoiceStatus::Cancelled->value => 10,
    ];

    public function run(): void
    {
        $admin = User::role('admin')->firstOrFail();

        $this->command->info('Creating ' . self::CUSTOMER_COUNT . ' customers...');
        $newCustomerIds = Customer::factory(self::CUSTOMER_COUNT)->create()->pluck('id');

        $this->command->info('Creating vehicles...');
        $newCustomerIds->each(function (int $customerId) {
            Vehicle::factory(fake()->numberBetween(1, 2))->create([
                'customer_id' => $customerId,
            ]);
        });

        $this->command->info('Creating ' . self::INVOICE_COUNT . ' invoices...');

        // Pre-build customer → [vehicle IDs] map to avoid N+1 in the invoice loop.
        $vehicleMap = Vehicle::whereIn('customer_id', $newCustomerIds)
            ->get(['id', 'customer_id'])
            ->groupBy('customer_id')
            ->map(fn ($vehicles) => $vehicles->pluck('id'));

        $statusPool = $this->buildStatusPool();

        for ($i = 1; $i <= self::INVOICE_COUNT; $i++) {
            $customerId = $newCustomerIds->random();
            $vehicleId  = $vehicleMap->get($customerId)?->random();

            Invoice::factory()->create([
                'customer_id' => $customerId,
                'vehicle_id'  => $vehicleId,
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

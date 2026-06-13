<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceOrderSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        $customers = Customer::query()->has('vehicles')->with('vehicles')->take(3)->get();

        if ($user === null || $customers->count() < 3) {
            return;
        }

        // One order per workshop phase: reception, in progress, closed.
        ServiceOrder::factory()->for($customers[0])->create([
            'vehicle_id' => $customers[0]->vehicles->first()->id,
            'created_by' => $user->id,
        ]);

        $inProgress = ServiceOrder::factory()->inProgress()->for($customers[1])->create([
            'vehicle_id' => $customers[1]->vehicles->first()->id,
            'created_by' => $user->id,
        ]);

        $closed = ServiceOrder::factory()->closed()->for($customers[2])->create([
            'vehicle_id' => $customers[2]->vehicles->first()->id,
            'created_by' => $user->id,
        ]);

        ServiceOrderItem::factory(2)->create(['service_order_id' => $inProgress->id]);
        ServiceOrderItem::factory(3)->create(['service_order_id' => $closed->id]);
    }
}

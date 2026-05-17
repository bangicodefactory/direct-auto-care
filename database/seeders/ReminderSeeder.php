<?php

namespace Database\Seeders;

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Models\Customer;
use App\Models\Reminder;
use Illuminate\Database\Seeder;

class ReminderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::with('vehicles')->get();

        if ($customers->isEmpty()) {
            return;
        }

        foreach ($customers->take(5) as $customer) {
            $vehicle = $customer->vehicles->first();

            if (! $vehicle) {
                continue;
            }

            // Vidange — due in ~2 weeks
            Reminder::create([
                'vehicle_id'    => $vehicle->id,
                'customer_id'   => $customer->id,
                'type'          => ReminderType::Vidange,
                'scheduled_for' => now()->addDays(14)->format('Y-m-d'),
                'status'        => ReminderStatus::Pending,
                'trigger_km'    => $vehicle->mileage ? $vehicle->mileage + 10000 : null,
                'notes'         => null,
            ]);

            // Visite technique — due in ~25 days
            Reminder::create([
                'vehicle_id'    => $vehicle->id,
                'customer_id'   => $customer->id,
                'type'          => ReminderType::VisiteTechnique,
                'scheduled_for' => now()->addDays(25)->format('Y-m-d'),
                'status'        => ReminderStatus::Pending,
                'trigger_km'    => null,
                'notes'         => null,
            ]);
        }

        // One already-sent vidange reminder for demo purposes
        $first = $customers->first();
        $vehicle = $first?->vehicles->first();

        if ($first && $vehicle) {
            Reminder::create([
                'vehicle_id'    => $vehicle->id,
                'customer_id'   => $first->id,
                'type'          => ReminderType::EntretienPeriodique,
                'scheduled_for' => now()->subDays(3)->format('Y-m-d'),
                'status'        => ReminderStatus::Sent,
                'trigger_km'    => null,
                'sent_at'       => now()->subDays(3),
                'notes'         => 'Entretien des 30 000 km.',
            ]);
        }
    }
}

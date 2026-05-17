<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $admin      = User::role('admin')->firstOrFail();
        $customers  = Customer::with('vehicles')->get();
        $serviceTypes = ServiceType::all();

        if ($customers->isEmpty()) {
            return;
        }

        // Helper: pick a customer + one of their vehicles
        $pick = function () use ($customers): array {
            $customer = $customers->random();
            $vehicle  = $customer->vehicles->first();
            return [$customer, $vehicle];
        };

        // ── Today — 3 appointments (morning + midday + afternoon) ────────────
        $todaySlots = [
            ['hour' => 8,  'minute' => 30, 'status' => AppointmentStatus::Confirmed, 'duration' => 60,  'types' => ['Vidange', 'Filtres']],
            ['hour' => 11, 'minute' => 0,  'status' => AppointmentStatus::Confirmed, 'duration' => 90,  'types' => ['Freins']],
            ['hour' => 14, 'minute' => 30, 'status' => AppointmentStatus::Pending,   'duration' => 45,  'types' => ['Batterie']],
        ];

        foreach ($todaySlots as $slot) {
            [$customer, $vehicle] = $pick();
            $appointment = Appointment::create([
                'customer_id'  => $customer->id,
                'vehicle_id'   => $vehicle?->id,
                'created_by'   => $admin->id,
                'scheduled_at' => today()->setTime($slot['hour'], $slot['minute']),
                'duration'     => $slot['duration'],
                'status'       => $slot['status'],
                'notes'        => null,
            ]);

            $ids = $serviceTypes->whereIn('name', $slot['types'])->pluck('id');
            $appointment->serviceTypes()->attach($ids);
        }

        // ── This week — 4 more appointments spread across the next 4 days ───
        $weekSlots = [
            ['offset' => 1, 'hour' => 9,  'minute' => 0,  'status' => AppointmentStatus::Confirmed, 'duration' => 120, 'types' => ['Climatisation']],
            ['offset' => 2, 'hour' => 10, 'minute' => 0,  'status' => AppointmentStatus::Pending,   'duration' => 60,  'types' => ['Pneus']],
            ['offset' => 3, 'hour' => 14, 'minute' => 0,  'status' => AppointmentStatus::Pending,   'duration' => 90,  'types' => ['Réparations']],
            ['offset' => 4, 'hour' => 16, 'minute' => 0,  'status' => AppointmentStatus::Confirmed, 'duration' => 60,  'types' => ['Vidange', 'Filtres']],
        ];

        foreach ($weekSlots as $slot) {
            [$customer, $vehicle] = $pick();
            $appointment = Appointment::create([
                'customer_id'  => $customer->id,
                'vehicle_id'   => $vehicle?->id,
                'created_by'   => $admin->id,
                'scheduled_at' => today()->addWeekdays($slot['offset'])->setTime($slot['hour'], $slot['minute']),
                'duration'     => $slot['duration'],
                'status'       => $slot['status'],
                'notes'        => null,
            ]);

            $ids = $serviceTypes->whereIn('name', $slot['types'])->pluck('id');
            $appointment->serviceTypes()->attach($ids);
        }
    }
}

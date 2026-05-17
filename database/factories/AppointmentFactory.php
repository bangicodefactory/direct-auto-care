<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id'  => Customer::factory(),
            'vehicle_id'   => null,
            'created_by'   => User::factory(),
            'scheduled_at' => fake()->dateTimeBetween('now', '+2 weeks'),
            'duration'     => fake()->randomElement([30, 45, 60, 90, 120]),
            'status'       => fake()->randomElement(AppointmentStatus::cases()),
            'notes'        => fake()->optional(0.4)->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => AppointmentStatus::Pending]);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => AppointmentStatus::Confirmed]);
    }

    public function completed(): static
    {
        return $this->state(['status' => AppointmentStatus::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => AppointmentStatus::Cancelled]);
    }

    public function today(): static
    {
        return $this->state([
            // Cap at 16 so a 30-min slot never runs past 17:00 closing time.
            'scheduled_at' => today()->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30])),
        ]);
    }
}

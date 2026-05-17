<?php

namespace Database\Factories;

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReminderFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(ReminderType::cases());

        return [
            'vehicle_id'   => Vehicle::factory(),
            'customer_id'  => Customer::factory(),
            'type'         => $type,
            'scheduled_for' => fake()->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'status'       => ReminderStatus::Pending,
            'trigger_km'   => $type === ReminderType::Vidange ? fake()->numberBetween(10000, 150000) : null,
            'sent_at'      => null,
            'notes'        => fake()->optional(0.3)->sentence(),
        ];
    }

    public function sent(): static
    {
        return $this->state([
            'status'  => ReminderStatus::Sent,
            'sent_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ]);
    }

    public function vidange(): static
    {
        return $this->state([
            'type'       => ReminderType::Vidange,
            'trigger_km' => fake()->numberBetween(10000, 150000),
        ]);
    }

    public function visiteTechnique(): static
    {
        return $this->state([
            'type'       => ReminderType::VisiteTechnique,
            'trigger_km' => null,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\ServiceOrderStatus;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceOrderFactory extends Factory
{
    public function definition(): array
    {
        $receivedAt = fake()->dateTimeBetween('-2 months', 'now');
        $year       = (int) $receivedAt->format('Y');

        return [
            'number'          => 'OS-' . $year . '-' . fake()->unique()->numerify('#####'),
            'appointment_id'  => null,
            'customer_id'     => Customer::factory(),
            'vehicle_id'      => Vehicle::factory(),
            'mileage_in'      => fake()->numberBetween(15_000, 220_000),
            'status'          => ServiceOrderStatus::Reception->value,
            'diagnosis_notes' => null,
            'internal_notes'  => fake()->optional(0.3)->sentence(),
            'quote_id'        => null,
            'invoice_id'      => null,
            'received_at'     => $receivedAt,
            'created_by'      => User::factory(),
        ];
    }

    /**
     * An order mid-repair, with the earlier phase timestamps filled in.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status'          => ServiceOrderStatus::InProgress->value,
            'diagnosis_notes' => fake()->sentence(),
            'diagnosed_at'    => fake()->dateTimeBetween('-1 month', '-2 weeks'),
            'quote_sent_at'   => fake()->dateTimeBetween('-2 weeks', '-1 week'),
            'in_progress_at'  => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * A fully closed order with every phase timestamp populated.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status'             => ServiceOrderStatus::Closed->value,
            'diagnosis_notes'    => fake()->sentence(),
            'diagnosed_at'       => fake()->dateTimeBetween('-2 months', '-6 weeks'),
            'quote_sent_at'      => fake()->dateTimeBetween('-6 weeks', '-5 weeks'),
            'in_progress_at'     => fake()->dateTimeBetween('-5 weeks', '-4 weeks'),
            'quality_control_at' => fake()->dateTimeBetween('-4 weeks', '-3 weeks'),
            'notified_at'        => fake()->dateTimeBetween('-3 weeks', '-2 weeks'),
            'ready_pickup_at'    => fake()->dateTimeBetween('-2 weeks', '-1 week'),
            'closed_at'          => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}

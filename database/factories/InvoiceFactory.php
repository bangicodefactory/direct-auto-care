<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $subtotalHt = fake()->randomFloat(2, 100, 5000);
        $discount   = 0.00;
        $tvaRate    = 20.00;
        $tvaAmount  = round(($subtotalHt - $discount) * $tvaRate / 100, 2);
        $totalTtc   = round($subtotalHt - $discount + $tvaAmount, 2);
        $issueDate  = fake()->dateTimeBetween('-1 year', 'now');
        $dueDate    = (clone $issueDate)->modify('+30 days');
        $fiscalYear = (int) $issueDate->format('Y');
        $type       = fake()->randomElement(InvoiceType::cases());
        $prefix     = $type === InvoiceType::Quote ? 'DEV' : 'FAC';

        return [
            'type'                   => $type->value,
            'number'                 => $prefix . '-' . $fiscalYear . '-' . fake()->unique()->numerify('####'),
            'customer_id'            => Customer::factory(),
            'vehicle_id'             => null,
            'issue_date'             => $issueDate->format('Y-m-d'),
            'due_date'               => $dueDate->format('Y-m-d'),
            'fiscal_year'            => $fiscalYear,
            'subtotal_ht'            => $subtotalHt,
            'discount'               => $discount,
            'tva_rate'               => $tvaRate,
            'tva_amount'             => $tvaAmount,
            'total_ttc'              => $totalTtc,
            'status'                 => fake()->randomElement(InvoiceStatus::cases())->value,
            'notes'                  => fake()->optional(0.3)->sentence(),
            'created_by'             => User::factory(),
            'converted_from_quote_id' => null,
        ];
    }
}

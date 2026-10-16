<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('invoice'));
    }

    public function rules(): array
    {
        return [
            'customer_id'           => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'vehicle_id'            => ['nullable', 'integer', Rule::exists('vehicles', 'id')->where('customer_id', $this->integer('customer_id'))->whereNull('deleted_at')],
            'issue_date'            => ['required', 'date'],
            'due_date'              => ['nullable', 'date', 'after_or_equal:issue_date'],
            'discount'              => ['nullable', 'numeric', 'min:0'],
            'tva_rate'              => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'                 => ['nullable', 'string', 'max:2000'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.product_id'    => ['nullable', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            // The saved line this row edits, so InvoiceService can keep its unit
            // snapshot server-side. Absent for lines added in this edit. The unit
            // itself is never accepted from the client.
            'items.*.id'            => ['nullable', 'integer', 'distinct', Rule::exists('invoice_items', 'id')->where('invoice_id', $this->route('invoice')->id)],
            // No is_active filter: editing an old document that references a
            // since-deactivated service must still validate.
            'items.*.service_id'    => ['nullable', 'integer', Rule::exists('services', 'id')->whereNull('deleted_at'), $this->serviceExclusiveWithProduct()],
            // Per-line vehicle is optional; when present it must belong to this invoice's customer.
            'items.*.vehicle_id'    => ['nullable', 'integer', Rule::exists('vehicles', 'id')->where('customer_id', $this->integer('customer_id'))->whereNull('deleted_at')],
            'items.*.description'   => ['required', 'string', 'max:255'],
            'items.*.quantity'      => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price_ht' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * A line references a product OR a service, never both. A closure rather
     * than `prohibits`: ConvertEmptyStringsToNull makes both keys always
     * present (as null) in the payload, which would trip `prohibits`.
     */
    protected function serviceExclusiveWithProduct(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $index = explode('.', $attribute)[1];

            if ($value !== null && $this->input("items.{$index}.product_id") !== null) {
                $fail('Une ligne ne peut pas référencer à la fois un produit et un service.');
            }
        };
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\InvoiceType;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Invoice::class);
    }

    public function rules(): array
    {
        return [
            'type'                  => ['required', Rule::enum(InvoiceType::class)],
            'customer_id'           => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'vehicle_id'            => ['nullable', 'integer', Rule::exists('vehicles', 'id')->where('customer_id', $this->integer('customer_id'))->whereNull('deleted_at')],
            'issue_date'            => ['required', 'date'],
            'due_date'              => ['nullable', 'date', 'after_or_equal:issue_date'],
            'discount'              => ['nullable', 'numeric', 'min:0'],
            'tva_rate'              => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'                 => ['nullable', 'string', 'max:2000'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.product_id'    => ['nullable', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            // Per-line vehicle is optional; when present it must belong to this invoice's customer.
            'items.*.vehicle_id'    => ['nullable', 'integer', Rule::exists('vehicles', 'id')->where('customer_id', $this->integer('customer_id'))->whereNull('deleted_at')],
            'items.*.description'   => ['required', 'string', 'max:500'],
            'items.*.quantity'      => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price_ht' => ['required', 'numeric', 'min:0'],
        ];
    }
}

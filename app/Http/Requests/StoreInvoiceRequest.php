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
            'type'                      => ['required', Rule::enum(InvoiceType::class)],
            'customer_id'               => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id'                => ['nullable', 'integer', 'exists:vehicles,id'],
            'issue_date'                => ['required', 'date'],
            'due_date'                  => ['nullable', 'date', 'after_or_equal:issue_date'],
            'discount'                  => ['nullable', 'numeric', 'min:0'],
            'tva_rate'                  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'                     => ['nullable', 'string', 'max:2000'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['nullable', 'integer', 'exists:products,id'],
            'items.*.description'       => ['required', 'string', 'max:500'],
            'items.*.quantity'          => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price_ht'     => ['required', 'numeric', 'min:0'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('invoice'));
    }

    public function rules(): array
    {
        return [
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

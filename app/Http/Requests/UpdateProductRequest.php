<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    /**
     * Same field rules as creation — product fields have identical constraints
     * on both paths — with only the sku unique rule relaxed to ignore the
     * product being edited. Overriding a key here is the escape hatch if the
     * two paths ever need to diverge on another field.
     */
    public function rules(): array
    {
        $rules = StoreProductRequest::fieldRules();

        $rules['sku'] = ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($this->route('product'))];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if (config('company.show_product_units')) {
            return;
        }

        $product = $this->route('product');

        $this->merge(['unit' => StoreProductRequest::withDefaultUnit(
            $this->all(),
            $product?->unit,
            $product?->category?->value,
        )['unit']]);
    }
}

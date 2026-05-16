<?php

namespace App\Http\Requests;

use App\Enums\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category'            => ['required', Rule::enum(ProductCategory::class)],
            'name'                => ['required', 'string', 'max:150'],
            'sku'                 => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product)],
            'unit'                => ['required', 'string', 'max:20'],
            'price_buy'           => ['nullable', 'numeric', 'min:0'],
            'price_sell'          => ['required', 'numeric', 'min:0'],
            'quantity_on_hand'    => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:1'],
        ];
    }
}

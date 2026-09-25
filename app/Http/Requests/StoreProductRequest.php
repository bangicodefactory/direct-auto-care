<?php

namespace App\Http\Requests;

use App\Enums\ProductCategory;
use App\Enums\ProductUnit;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        return self::fieldRules();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(self::withDefaultUnit($this->all()));
    }

    /**
     * When the deployment hides product units, the form has no unit field:
     * fill it so the unchanged `required` rule still holds. The fallback is the
     * product's current unit on update ($existingUnit), otherwise the category
     * default. When units are shown, an empty unit is left alone and rejected.
     *
     * Runs before validation, so `category` may be anything: a non-string value
     * is treated as unknown and left for the enum rule to reject.
     *
     * Shared with UpdateProductRequest and the bulk import.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withDefaultUnit(array $data, ?string $existingUnit = null): array
    {
        if (config('company.show_product_units')) {
            return $data;
        }

        $unit = $data['unit'] ?? null;

        if ($unit !== null && $unit !== '') {
            return $data;
        }

        if ($existingUnit !== null && $existingUnit !== '') {
            $data['unit'] = $existingUnit;

            return $data;
        }

        $rawCategory = $data['category'] ?? null;
        $category    = is_string($rawCategory)
            ? (ProductCategory::tryFrom($rawCategory) ?? ProductCategory::Autre)
            : ProductCategory::Autre;

        $data['unit'] = ProductUnit::defaultFor($category)->value;

        return $data;
    }

    /**
     * Validation rules for a product's fields. Shared with the bulk-import
     * service and with UpdateProductRequest so every write path validates
     * against the same constraints — a rule added here applies to all three.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function fieldRules(): array
    {
        return [
            'category'            => ['required', Rule::enum(ProductCategory::class)],
            'name'                => ['required', 'string', 'max:150'],
            'sku'                 => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')],
            'unit'                => ['required', Rule::enum(ProductUnit::class)],
            'price_buy'           => ['nullable', 'numeric', 'min:0'],
            'price_sell'          => ['required', 'numeric', 'min:0'],
            'quantity_on_hand'    => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:1'],
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ProductCategory;
use App\Enums\ProductUnit;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProductRequestTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUpdateRequest(Product $product): UpdateProductRequest
    {
        $request = new UpdateProductRequest();
        $request->setRouteResolver(function () use ($product) {
            return new class ($product) {
                public function __construct(private Product $product)
                {
                }

                public function parameter(string $key, mixed $default = null): mixed
                {
                    return $key === 'product' ? $this->product : $default;
                }
            };
        });

        return $request;
    }

    private function validPayload(): array
    {
        return [
            'category'            => ProductCategory::Huile->value,
            'name'                => 'Huile moteur 5W-30 1L',
            'sku'                 => 'HU-5W30-1L',
            'unit'                => 'litre',
            'price_buy'           => 35.00,
            'price_sell'          => 55.00,
            'quantity_on_hand'    => 10,
            'low_stock_threshold' => 3,
        ];
    }

    // -------------------------------------------------------------------------
    // StoreProductRequest — valid payload
    // -------------------------------------------------------------------------

    public function test_store_valid_payload_passes(): void
    {
        $v = Validator::make($this->validPayload(), (new StoreProductRequest())->rules());

        $this->assertFalse($v->fails());
    }

    // -------------------------------------------------------------------------
    // StoreProductRequest — required fields
    // -------------------------------------------------------------------------

    public function test_store_category_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['category']);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('category', $v->errors()->toArray());
    }

    public function test_store_name_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['name']);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_store_unit_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['unit']);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('unit', $v->errors()->toArray());
    }

    public function test_store_price_sell_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['price_sell']);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('price_sell', $v->errors()->toArray());
    }

    public function test_store_quantity_on_hand_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['quantity_on_hand']);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('quantity_on_hand', $v->errors()->toArray());
    }

    public function test_store_low_stock_threshold_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['low_stock_threshold']);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('low_stock_threshold', $v->errors()->toArray());
    }

    // -------------------------------------------------------------------------
    // StoreProductRequest — category enum
    // -------------------------------------------------------------------------

    public function test_store_category_rejects_invalid_value(): void
    {
        $payload = array_merge($this->validPayload(), ['category' => 'invalide']);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('category', $v->errors()->toArray());
    }

    public function test_store_category_accepts_all_enum_values(): void
    {
        $rules = (new StoreProductRequest())->rules();

        foreach (ProductCategory::cases() as $category) {
            $v = Validator::make(
                array_merge($this->validPayload(), ['category' => $category->value]),
                $rules
            );
            $this->assertArrayNotHasKey('category', $v->errors()->toArray(), "category '{$category->value}' should be valid");
        }
    }

    // -------------------------------------------------------------------------
    // StoreProductRequest — SKU uniqueness
    // -------------------------------------------------------------------------

    public function test_store_sku_is_optional(): void
    {
        $payload = $this->validPayload();
        unset($payload['sku']);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertFalse($v->fails());
    }

    public function test_store_sku_unique_is_enforced(): void
    {
        Product::factory()->create(['sku' => 'HU-5W30-1L']);

        $v = Validator::make($this->validPayload(), (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('sku', $v->errors()->toArray());
    }

    public function test_store_sku_max_length_is_enforced(): void
    {
        $payload = array_merge($this->validPayload(), ['sku' => str_repeat('X', 51)]);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('sku', $v->errors()->toArray());
    }

    public function test_store_sku_of_soft_deleted_product_is_not_reusable(): void
    {
        $deleted = Product::factory()->create(['sku' => 'HU-5W30-1L']);
        $deleted->delete();

        $v = Validator::make($this->validPayload(), (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('sku', $v->errors()->toArray());
    }

    // -------------------------------------------------------------------------
    // StoreProductRequest — non-negative quantities
    // -------------------------------------------------------------------------

    public function test_store_quantity_on_hand_rejects_negative(): void
    {
        $payload = array_merge($this->validPayload(), ['quantity_on_hand' => -1]);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('quantity_on_hand', $v->errors()->toArray());
    }

    public function test_store_quantity_on_hand_accepts_zero(): void
    {
        $payload = array_merge($this->validPayload(), ['quantity_on_hand' => 0]);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayNotHasKey('quantity_on_hand', $v->errors()->toArray());
    }

    public function test_store_low_stock_threshold_rejects_zero(): void
    {
        $payload = array_merge($this->validPayload(), ['low_stock_threshold' => 0]);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('low_stock_threshold', $v->errors()->toArray());
    }

    public function test_store_price_buy_rejects_negative(): void
    {
        $payload = array_merge($this->validPayload(), ['price_buy' => -1]);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('price_buy', $v->errors()->toArray());
    }

    public function test_store_price_sell_rejects_negative(): void
    {
        $payload = array_merge($this->validPayload(), ['price_sell' => -1]);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('price_sell', $v->errors()->toArray());
    }

    public function test_store_price_buy_is_optional(): void
    {
        $payload = $this->validPayload();
        unset($payload['price_buy']);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertFalse($v->fails());
    }

    // -------------------------------------------------------------------------
    // StoreProductRequest — max lengths
    // -------------------------------------------------------------------------

    public function test_store_name_max_length_is_enforced(): void
    {
        $payload = array_merge($this->validPayload(), ['name' => str_repeat('a', 151)]);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_store_unit_must_be_a_known_unit(): void
    {
        $payload = array_merge($this->validPayload(), ['unit' => 'tonneau']);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('unit', $v->errors()->toArray());
    }

    public function test_store_unit_rejects_non_canonical_synonyms(): void
    {
        // The form submits canonical values; raw synonyms ("L") are only
        // accepted on the import path, which normalizes before validating.
        $payload = array_merge($this->validPayload(), ['unit' => 'L']);

        $v = Validator::make($payload, (new StoreProductRequest())->rules());

        $this->assertArrayHasKey('unit', $v->errors()->toArray());
    }

    public function test_update_unit_must_be_a_known_unit(): void
    {
        $product = Product::factory()->create();
        $payload = array_merge($this->validPayload(), ['sku' => null, 'unit' => 'tonneau']);

        $v = Validator::make($payload, $this->makeUpdateRequest($product)->rules());

        $this->assertArrayHasKey('unit', $v->errors()->toArray());
    }

    public function test_store_unit_accepts_every_canonical_unit(): void
    {
        foreach (ProductUnit::cases() as $unit) {
            $payload = array_merge($this->validPayload(), ['sku' => null, 'unit' => $unit->value]);

            $v = Validator::make($payload, (new StoreProductRequest())->rules());

            $this->assertArrayNotHasKey('unit', $v->errors()->toArray(), "Expected unit '{$unit->value}' to pass");
        }
    }

    // -------------------------------------------------------------------------
    // UpdateProductRequest — valid payload
    // -------------------------------------------------------------------------

    public function test_update_valid_payload_passes(): void
    {
        $product = Product::factory()->create();
        $rules   = $this->makeUpdateRequest($product)->rules();

        $v = Validator::make($this->validPayload(), $rules);

        $this->assertFalse($v->fails());
    }

    // -------------------------------------------------------------------------
    // UpdateProductRequest — SKU uniqueness (ignore self)
    // -------------------------------------------------------------------------

    public function test_update_sku_allows_own(): void
    {
        $product = Product::factory()->create(['sku' => 'HU-5W30-1L']);
        $rules   = $this->makeUpdateRequest($product)->rules();

        $v = Validator::make($this->validPayload(), $rules);

        $this->assertArrayNotHasKey('sku', $v->errors()->toArray());
    }

    public function test_update_sku_rejects_another_products_sku(): void
    {
        Product::factory()->create(['sku' => 'OTHER-SKU']);
        $product = Product::factory()->create(['sku' => 'MY-SKU']);
        $rules   = $this->makeUpdateRequest($product)->rules();

        $payload = array_merge($this->validPayload(), ['sku' => 'OTHER-SKU']);
        $v       = Validator::make($payload, $rules);

        $this->assertArrayHasKey('sku', $v->errors()->toArray());
    }

    // -------------------------------------------------------------------------
    // UpdateProductRequest — non-negative quantities
    // -------------------------------------------------------------------------

    public function test_update_quantity_on_hand_rejects_negative(): void
    {
        $product = Product::factory()->create();
        $rules   = $this->makeUpdateRequest($product)->rules();
        $payload = array_merge($this->validPayload(), ['quantity_on_hand' => -1]);

        $v = Validator::make($payload, $rules);

        $this->assertArrayHasKey('quantity_on_hand', $v->errors()->toArray());
    }

    public function test_update_quantity_on_hand_accepts_zero(): void
    {
        $product = Product::factory()->create();
        $rules   = $this->makeUpdateRequest($product)->rules();
        $payload = array_merge($this->validPayload(), ['quantity_on_hand' => 0]);

        $v = Validator::make($payload, $rules);

        $this->assertArrayNotHasKey('quantity_on_hand', $v->errors()->toArray());
    }

    public function test_update_low_stock_threshold_rejects_zero(): void
    {
        $product = Product::factory()->create();
        $rules   = $this->makeUpdateRequest($product)->rules();
        $payload = array_merge($this->validPayload(), ['low_stock_threshold' => 0]);

        $v = Validator::make($payload, $rules);

        $this->assertArrayHasKey('low_stock_threshold', $v->errors()->toArray());
    }

    public function test_update_sku_allows_null_when_other_products_also_have_null_sku(): void
    {
        Product::factory()->create(['sku' => null]);
        $product = Product::factory()->create(['sku' => 'MY-SKU']);
        $rules   = $this->makeUpdateRequest($product)->rules();
        $payload = array_merge($this->validPayload(), ['sku' => null]);

        $v = Validator::make($payload, $rules);

        $this->assertArrayNotHasKey('sku', $v->errors()->toArray());
    }
}

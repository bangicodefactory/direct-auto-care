<?php

namespace Tests\Unit\Models;

use App\Enums\ProductCategory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_low_stock_true_when_quantity_below_threshold(): void
    {
        $product = Product::factory()->create([
            'quantity_on_hand'    => 2,
            'low_stock_threshold' => 5,
        ]);

        $this->assertTrue($product->is_low_stock);
    }

    public function test_is_low_stock_false_when_quantity_equals_threshold(): void
    {
        $product = Product::factory()->create([
            'quantity_on_hand'    => 5,
            'low_stock_threshold' => 5,
        ]);

        $this->assertFalse($product->is_low_stock);
    }

    public function test_is_low_stock_false_when_quantity_above_threshold(): void
    {
        $product = Product::factory()->create([
            'quantity_on_hand'    => 10,
            'low_stock_threshold' => 5,
        ]);

        $this->assertFalse($product->is_low_stock);
    }

    public function test_scope_low_stock_returns_only_low_stock_products(): void
    {
        Product::factory()->create(['quantity_on_hand' => 0, 'low_stock_threshold' => 5]);
        Product::factory()->create(['quantity_on_hand' => 2, 'low_stock_threshold' => 5]);
        Product::factory()->create(['quantity_on_hand' => 5, 'low_stock_threshold' => 5]);
        Product::factory()->create(['quantity_on_hand' => 10, 'low_stock_threshold' => 5]);

        $results = Product::lowStock()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('quantity_on_hand', 0));
        $this->assertTrue($results->contains('quantity_on_hand', 2));
    }

    public function test_category_is_cast_to_product_category_enum(): void
    {
        $product = Product::factory()->create(['category' => ProductCategory::Huile->value]);

        $this->assertInstanceOf(ProductCategory::class, $product->category);
        $this->assertEquals(ProductCategory::Huile, $product->category);
    }

    public function test_stock_movements_relationship(): void
    {
        $product = Product::factory()->create();
        $user    = User::factory()->create();

        StockMovement::factory()->count(3)->create([
            'product_id' => $product->id,
            'user_id'    => $user->id,
        ]);

        // Movements belonging to another product must not bleed in
        Product::factory()->has(StockMovement::factory()->count(2))->create();

        $this->assertCount(3, $product->stockMovements);
    }
}

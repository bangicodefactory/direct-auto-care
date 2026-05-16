<?php

namespace Tests\Unit\Models;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_type_is_cast_to_stock_movement_type_enum(): void
    {
        $movement = StockMovement::factory()->create(['type' => StockMovementType::In->value]);

        $this->assertInstanceOf(StockMovementType::class, $movement->type);
        $this->assertEquals(StockMovementType::In, $movement->type);
    }

    public function test_product_relationship_loads_soft_deleted_product(): void
    {
        $product  = Product::factory()->create();
        $user     = User::factory()->create();
        $movement = StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id'    => $user->id,
        ]);

        $product->delete();

        $this->assertNotNull($movement->product);
        $this->assertEquals($product->id, $movement->product->id);
        $this->assertTrue($movement->product->trashed());
    }

    public function test_user_relationship(): void
    {
        $user     = User::factory()->create();
        $movement = StockMovement::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $movement->user->id);
    }

    public function test_invoice_relationship_is_nullable(): void
    {
        $movement = StockMovement::factory()->create(['invoice_id' => null]);

        $this->assertNull($movement->invoice_id);
        $this->assertNull($movement->invoice);
    }
}

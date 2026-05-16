<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests — IST-47
 *
 * Verify the two critical stock-via-invoice scenarios end-to-end:
 *  1. Out movement linked to an invoice → stock decremented + movement logged
 *  2. Quantity exceeds stock → French error, no DB writes, HTTP 422
 */
class StockInvoiceSmokeTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->service = new StockService();
    }

    // -------------------------------------------------------------------------
    // Scenario 1 — stock decrement via invoice
    // -------------------------------------------------------------------------

    public function test_stock_is_decremented_and_movement_logged_when_invoiced(): void
    {
        $product  = Product::factory()->create(['quantity_on_hand' => 10]);
        $user     = User::factory()->create();
        $customer = Customer::factory()->create();
        $invoice  = Invoice::create(['customer_id' => $customer->id]);

        $movement = $this->service->recordMovement(
            product: $product,
            type: StockMovementType::Out,
            quantity: 3,
            reason: 'Vente facture #' . $invoice->id,
            invoice: $invoice,
            user: $user,
        );

        // Stock correctly decremented
        $this->assertEquals(7, $product->fresh()->quantity_on_hand);

        // Movement record carries the right values
        $this->assertEquals(-3, $movement->quantity);
        $this->assertEquals(StockMovementType::Out, $movement->type);
        $this->assertEquals($invoice->id, $movement->invoice_id);
        $this->assertEquals($user->id, $movement->user_id);
        $this->assertEquals('Vente facture #' . $invoice->id, $movement->reason);
    }

    // -------------------------------------------------------------------------
    // Scenario 2 — insufficient stock: French error + no DB writes
    // -------------------------------------------------------------------------

    public function test_insufficient_stock_throws_french_error_and_leaves_db_unchanged(): void
    {
        $product  = Product::factory()->create(['name' => 'Filtre à huile', 'quantity_on_hand' => 2]);
        $user     = User::factory()->create();
        $customer = Customer::factory()->create();
        $invoice  = Invoice::create(['customer_id' => $customer->id]);

        try {
            $this->service->recordMovement(
                product: $product,
                type: StockMovementType::Out,
                quantity: 5,
                reason: null,
                invoice: $invoice,
                user: $user,
            );
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException $e) {
            // French error message includes product name and current stock
            $this->assertStringContainsString('Stock insuffisant', $e->getMessage());
            $this->assertStringContainsString('Filtre à huile', $e->getMessage());
            $this->assertStringContainsString('2', $e->getMessage());
        }

        // Quantity on hand is unchanged
        $this->assertEquals(2, $product->fresh()->quantity_on_hand);

        // No movement record was created for this invoice
        $this->assertDatabaseMissing('stock_movements', ['invoice_id' => $invoice->id]);
    }

    // -------------------------------------------------------------------------
    // Scenario 2 (HTTP) — insufficient stock returns 422 with French message
    // -------------------------------------------------------------------------

    public function test_insufficient_stock_http_response_is_422_with_french_message(): void
    {
        $admin   = User::factory()->create()->assignRole('admin');
        $product = Product::factory()->create(['name' => 'Bougie allumage', 'quantity_on_hand' => 1]);

        $this->actingAs($admin)
            ->postJson(route('products.adjust-stock', $product), ['quantity' => -5])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'Stock insuffisant')
                && str_contains($msg, 'Bougie allumage'));
    }
}

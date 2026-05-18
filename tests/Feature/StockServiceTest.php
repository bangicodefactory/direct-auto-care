<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Services\StockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        $this->service = new StockService();
    }

    // -------------------------------------------------------------------------
    // In movements
    // -------------------------------------------------------------------------

    public function test_in_movement_increases_quantity(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $user    = User::factory()->create();

        $this->service->recordMovement($product, StockMovementType::In, 5, null, null, $user);

        $this->assertEquals(15, $product->fresh()->quantity_on_hand);
    }

    public function test_in_movement_creates_stock_movement_record(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 0]);
        $user    = User::factory()->create();

        $movement = $this->service->recordMovement($product, StockMovementType::In, 20, 'Réapprovisionnement', null, $user);

        $this->assertInstanceOf(StockMovement::class, $movement);
        $this->assertEquals($product->id, $movement->product_id);
        $this->assertEquals(StockMovementType::In, $movement->type);
        $this->assertEquals(20, $movement->quantity);
        $this->assertEquals('Réapprovisionnement', $movement->reason);
        $this->assertEquals($user->id, $movement->user_id);
    }

    // -------------------------------------------------------------------------
    // Out movements
    // -------------------------------------------------------------------------

    public function test_out_movement_decreases_quantity(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $user    = User::factory()->create();

        $this->service->recordMovement($product, StockMovementType::Out, 3, null, null, $user);

        $this->assertEquals(7, $product->fresh()->quantity_on_hand);
    }

    public function test_out_movement_stores_negative_delta(): void
    {
        $product  = Product::factory()->create(['quantity_on_hand' => 10]);
        $user     = User::factory()->create();

        $movement = $this->service->recordMovement($product, StockMovementType::Out, 4, null, null, $user);

        $this->assertEquals(-4, $movement->quantity);
    }

    public function test_out_movement_to_zero_succeeds(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 5]);
        $user    = User::factory()->create();

        $this->service->recordMovement($product, StockMovementType::Out, 5, null, null, $user);

        $this->assertEquals(0, $product->fresh()->quantity_on_hand);
    }

    public function test_out_movement_throws_when_would_go_negative(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 3]);
        $user    = User::factory()->create();

        $this->expectException(InsufficientStockException::class);

        $this->service->recordMovement($product, StockMovementType::Out, 4, null, null, $user);
    }

    public function test_quantity_unchanged_after_failed_out_movement(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 3]);
        $user    = User::factory()->create();

        try {
            $this->service->recordMovement($product, StockMovementType::Out, 10, null, null, $user);
        } catch (InsufficientStockException) {
        }

        $this->assertEquals(3, $product->fresh()->quantity_on_hand);
        $this->assertEquals(0, StockMovement::where('product_id', $product->id)->count());
    }

    // -------------------------------------------------------------------------
    // Adjustment movements
    // -------------------------------------------------------------------------

    public function test_positive_adjustment_increases_quantity(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 5]);
        $user    = User::factory()->create();

        $this->service->recordMovement($product, StockMovementType::Adjustment, 3, 'Inventaire', null, $user);

        $this->assertEquals(8, $product->fresh()->quantity_on_hand);
    }

    public function test_negative_adjustment_decreases_quantity(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $user    = User::factory()->create();

        $this->service->recordMovement($product, StockMovementType::Adjustment, -4, 'Perte', null, $user);

        $this->assertEquals(6, $product->fresh()->quantity_on_hand);
    }

    public function test_negative_adjustment_throws_when_would_go_negative(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 2]);
        $user    = User::factory()->create();

        $this->expectException(InsufficientStockException::class);

        $this->service->recordMovement($product, StockMovementType::Adjustment, -5, null, null, $user);
    }

    // -------------------------------------------------------------------------
    // Invariant: sequential decrements never leave quantity negative
    // -------------------------------------------------------------------------

    public function test_sequential_decrements_never_leave_quantity_negative(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 1]);
        $user    = User::factory()->create();

        // First decrement exhausts stock
        $this->service->recordMovement($product, StockMovementType::Out, 1, null, null, $user);
        $this->assertEquals(0, $product->fresh()->quantity_on_hand);

        // Second decrement must be rejected — this is the race-condition guard:
        // lockForUpdate() serialises concurrent requests so both see quantity=0
        // after the first commits, and the second throws here.
        try {
            $this->service->recordMovement($product->fresh(), StockMovementType::Out, 1, null, null, $user);
            $this->fail('Expected InsufficientStockException');
        } catch (InsufficientStockException) {
        }

        $this->assertEquals(0, $product->fresh()->quantity_on_hand);
    }

    // -------------------------------------------------------------------------
    // Invoice link
    // -------------------------------------------------------------------------

    public function test_movement_records_invoice_id_when_invoice_provided(): void
    {
        $product  = Product::factory()->create(['quantity_on_hand' => 10]);
        $user     = User::factory()->create();
        $customer = Customer::factory()->create();
        $invoice  = Invoice::factory()->create(['customer_id' => $customer->id]);

        $movement = $this->service->recordMovement($product, StockMovementType::Out, 2, null, $invoice, $user);

        $this->assertEquals($invoice->id, $movement->invoice_id);
    }

    public function test_movement_invoice_id_is_null_when_no_invoice(): void
    {
        $product  = Product::factory()->create(['quantity_on_hand' => 10]);
        $user     = User::factory()->create();

        $movement = $this->service->recordMovement($product, StockMovementType::Out, 1, null, null, $user);

        $this->assertNull($movement->invoice_id);
    }

    // -------------------------------------------------------------------------
    // Zero-quantity guard
    // -------------------------------------------------------------------------

    public function test_zero_quantity_throws_invalid_argument_exception(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $user    = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->service->recordMovement($product, StockMovementType::Out, 0, null, null, $user);
    }

    // -------------------------------------------------------------------------
    // Low-stock notifications (edge-triggered)
    // -------------------------------------------------------------------------

    public function test_low_stock_notification_sent_when_crossing_below_threshold(): void
    {
        $admin   = User::factory()->create()->assignRole('admin');
        $product = Product::factory()->create(['quantity_on_hand' => 6, 'low_stock_threshold' => 5]);
        $user    = User::factory()->create();

        $this->service->recordMovement($product, StockMovementType::Out, 2, null, null, $user);

        Notification::assertSentTo($admin, LowStockNotification::class);
    }

    public function test_low_stock_notification_not_sent_when_already_below_threshold(): void
    {
        User::factory()->create()->assignRole('admin');
        $product = Product::factory()->create(['quantity_on_hand' => 3, 'low_stock_threshold' => 5]);
        $user    = User::factory()->create();

        $this->service->recordMovement($product, StockMovementType::Out, 1, null, null, $user);

        Notification::assertNothingSent();
    }

    public function test_low_stock_notification_not_sent_when_stock_stays_above_threshold(): void
    {
        User::factory()->create()->assignRole('admin');
        $product = Product::factory()->create(['quantity_on_hand' => 20, 'low_stock_threshold' => 5]);
        $user    = User::factory()->create();

        $this->service->recordMovement($product, StockMovementType::Out, 5, null, null, $user);

        Notification::assertNothingSent();
    }

    // -------------------------------------------------------------------------
    // French error message
    // -------------------------------------------------------------------------

    public function test_exception_message_is_in_french(): void
    {
        $product = Product::factory()->create(['name' => 'Huile 5W-30', 'quantity_on_hand' => 0]);
        $user    = User::factory()->create();

        try {
            $this->service->recordMovement($product, StockMovementType::Out, 1, null, null, $user);
            $this->fail('Expected InsufficientStockException');
        } catch (InsufficientStockException $e) {
            $this->assertStringContainsString('Stock insuffisant', $e->getMessage());
            $this->assertStringContainsString('Huile 5W-30', $e->getMessage());
        }
    }
}

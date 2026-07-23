<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cancelling or deleting a facture returns its product stock to inventory —
 * exactly once, even on the cancel→delete path. Devis never touch stock.
 */
class InvoiceStockRestoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    /**
     * Create a document through the real store endpoint so stock is decremented
     * exactly as in production.
     */
    private function storeDocument(User $user, Product $product, string $type = 'invoice'): Invoice
    {
        $customer = Customer::factory()->create();

        $this->actingAs($user)->post(route('invoices.store'), [
            'type'        => $type,
            'customer_id' => $customer->id,
            'issue_date'  => now()->toDateString(),
            'discount'    => 0,
            'tva_rate'    => 20,
            'items'       => [[
                'product_id'    => $product->id,
                'description'   => $product->name,
                'quantity'      => 3,
                'unit_price_ht' => 100,
            ]],
        ])->assertRedirect();

        return Invoice::latest('id')->firstOrFail();
    }

    public function test_cancelling_a_facture_restores_stock(): void
    {
        $admin   = $this->admin();
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $invoice = $this->storeDocument($admin, $product);

        $this->assertSame(7, $product->refresh()->quantity_on_hand);

        $this->actingAs($admin)
            ->post(route('invoices.cancel', $invoice))
            ->assertRedirect();

        $this->assertSame(10, $product->refresh()->quantity_on_hand);
        $this->assertSame(InvoiceStatus::Cancelled, $invoice->refresh()->status);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'invoice_id' => $invoice->id,
            'quantity'   => 3,
            'reason'     => 'Annulation facture',
        ]);
    }

    public function test_deleting_a_facture_restores_stock(): void
    {
        $admin   = $this->admin();
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $invoice = $this->storeDocument($admin, $product);

        $this->assertSame(7, $product->refresh()->quantity_on_hand);

        $this->actingAs($admin)
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'));

        $this->assertSame(10, $product->refresh()->quantity_on_hand);
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'invoice_id' => $invoice->id,
            'quantity'   => 3,
            'reason'     => 'Suppression facture',
        ]);
    }

    public function test_cancel_then_delete_restores_stock_only_once(): void
    {
        $admin   = $this->admin();
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $invoice = $this->storeDocument($admin, $product);

        $this->actingAs($admin)->post(route('invoices.cancel', $invoice));
        $this->assertSame(10, $product->refresh()->quantity_on_hand);

        $this->actingAs($admin)->delete(route('invoices.destroy', $invoice));

        // Still 10 — the delete must NOT restore a second time.
        $this->assertSame(10, $product->refresh()->quantity_on_hand);
        // Exactly one Out (creation) and one In (cancellation) movement.
        $this->assertSame(
            2,
            $product->stockMovements()->where('invoice_id', $invoice->id)->count()
        );
    }

    public function test_cancelling_a_devis_touches_no_stock(): void
    {
        $admin   = $this->admin();
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $invoice = $this->storeDocument($admin, $product, 'quote');

        // Quotes never decrement stock on creation.
        $this->assertSame(10, $product->refresh()->quantity_on_hand);

        $this->actingAs($admin)->post(route('invoices.cancel', $invoice));

        $this->assertSame(10, $product->refresh()->quantity_on_hand);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_deleting_a_devis_touches_no_stock(): void
    {
        $admin   = $this->admin();
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $invoice = $this->storeDocument($admin, $product, 'quote');

        $this->actingAs($admin)->delete(route('invoices.destroy', $invoice));

        $this->assertSame(10, $product->refresh()->quantity_on_hand);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_deleting_a_paid_facture_restores_stock(): void
    {
        $admin   = $this->admin();
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $invoice = $this->storeDocument($admin, $product);

        $invoice->update(['status' => InvoiceStatus::Paid->value]);

        $this->actingAs($admin)->delete(route('invoices.destroy', $invoice));

        $this->assertSame(10, $product->refresh()->quantity_on_hand);
    }

    public function test_cancelling_with_soft_deleted_product_skips_restore_but_cancels(): void
    {
        $admin   = $this->admin();
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $invoice = $this->storeDocument($admin, $product);

        $product->delete();

        $this->actingAs($admin)
            ->post(route('invoices.cancel', $invoice))
            ->assertRedirect();

        // Restore is skipped (product gone) but the cancellation still lands.
        $this->assertSame(InvoiceStatus::Cancelled, $invoice->refresh()->status);
    }
}

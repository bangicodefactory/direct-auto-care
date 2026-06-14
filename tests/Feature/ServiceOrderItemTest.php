<?php

namespace Tests\Feature;

use App\Enums\ServiceOrderStatus;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function employee(): User
    {
        return User::factory()->create()->assignRole('employee');
    }

    public function test_adding_a_product_line_decrements_stock(): void
    {
        $order   = ServiceOrder::factory()->inProgress()->create();
        $product = Product::factory()->create(['quantity_on_hand' => 10]);

        $this->actingAs($this->employee())
            ->post(route('workshop.items.store', $order), [
                'product_id'    => $product->id,
                'quantity'      => 3,
                'unit_price_ht' => 150,
            ])
            ->assertRedirect();

        $this->assertSame(7, $product->refresh()->quantity_on_hand);
        $this->assertDatabaseHas('service_order_items', [
            'service_order_id' => $order->id,
            'product_id'       => $product->id,
            'quantity'         => 3,
            'line_total_ht'    => 450,
        ]);
    }

    public function test_product_line_defaults_description_to_product_name(): void
    {
        $order   = ServiceOrder::factory()->inProgress()->create();
        $product = Product::factory()->create(['name' => 'Plaquettes avant', 'quantity_on_hand' => 5]);

        $this->actingAs($this->employee())
            ->post(route('workshop.items.store', $order), [
                'product_id'    => $product->id,
                'quantity'      => 1,
                'unit_price_ht' => 200,
            ]);

        $this->assertDatabaseHas('service_order_items', [
            'service_order_id' => $order->id,
            'description'      => 'Plaquettes avant',
        ]);
    }

    public function test_free_text_labour_line_touches_no_stock(): void
    {
        $order = ServiceOrder::factory()->inProgress()->create();

        $this->actingAs($this->employee())
            ->post(route('workshop.items.store', $order), [
                'description'   => "Main d'œuvre diagnostic",
                'quantity'      => 2,
                'unit_price_ht' => 120,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('service_order_items', [
            'service_order_id' => $order->id,
            'product_id'       => null,
            'description'      => "Main d'œuvre diagnostic",
            'line_total_ht'    => 240,
        ]);
    }

    public function test_insufficient_stock_is_rejected_with_a_french_error(): void
    {
        $order   = ServiceOrder::factory()->inProgress()->create();
        $product = Product::factory()->create(['quantity_on_hand' => 2]);

        $this->actingAs($this->employee())
            ->post(route('workshop.items.store', $order), [
                'product_id'    => $product->id,
                'quantity'      => 5,
                'unit_price_ht' => 100,
            ])
            ->assertSessionHasErrors(['quantity' => 'Stock insuffisant pour « ' . $product->name . ' » (disponible : 2, demandé : 5).']);

        $this->assertSame(2, $product->refresh()->quantity_on_hand);
        $this->assertDatabaseMissing('service_order_items', ['service_order_id' => $order->id]);
    }

    public function test_removing_a_product_line_re_increments_stock(): void
    {
        $order   = ServiceOrder::factory()->inProgress()->create();
        $product = Product::factory()->create(['quantity_on_hand' => 4]);
        $item    = ServiceOrderItem::factory()->create([
            'service_order_id' => $order->id,
            'product_id'       => $product->id,
            'quantity'         => 3,
        ]);

        $this->actingAs($this->employee())
            ->delete(route('workshop.items.destroy', [$order, $item]))
            ->assertRedirect();

        $this->assertSame(7, $product->refresh()->quantity_on_hand);
        $this->assertDatabaseMissing('service_order_items', ['id' => $item->id]);
    }

    public function test_removing_a_free_text_line_does_not_touch_stock(): void
    {
        $order = ServiceOrder::factory()->inProgress()->create();
        $item  = ServiceOrderItem::factory()->create([
            'service_order_id' => $order->id,
            'product_id'       => null,
        ]);

        $this->actingAs($this->employee())
            ->delete(route('workshop.items.destroy', [$order, $item]))
            ->assertRedirect();

        $this->assertDatabaseMissing('service_order_items', ['id' => $item->id]);
    }

    public function test_parts_cannot_be_added_unless_in_progress(): void
    {
        $order   = ServiceOrder::factory()->create(['status' => ServiceOrderStatus::Diagnosis->value]);
        $product = Product::factory()->create(['quantity_on_hand' => 10]);

        $this->actingAs($this->employee())
            ->post(route('workshop.items.store', $order), [
                'product_id'    => $product->id,
                'quantity'      => 1,
                'unit_price_ht' => 100,
            ])
            ->assertStatus(422);

        $this->assertSame(10, $product->refresh()->quantity_on_hand);
        $this->assertDatabaseMissing('service_order_items', ['service_order_id' => $order->id]);
    }

    public function test_parts_cannot_be_removed_unless_in_progress(): void
    {
        $order = ServiceOrder::factory()->closed()->create();
        $item  = ServiceOrderItem::factory()->create(['service_order_id' => $order->id, 'product_id' => null]);

        $this->actingAs($this->employee())
            ->delete(route('workshop.items.destroy', [$order, $item]))
            ->assertStatus(422);

        $this->assertDatabaseHas('service_order_items', ['id' => $item->id]);
    }

    public function test_item_from_another_order_cannot_be_removed(): void
    {
        $order = ServiceOrder::factory()->inProgress()->create();
        $other = ServiceOrder::factory()->inProgress()->create();
        $item  = ServiceOrderItem::factory()->create(['service_order_id' => $other->id, 'product_id' => null]);

        $this->actingAs($this->employee())
            ->delete(route('workshop.items.destroy', [$order, $item]))
            ->assertNotFound();

        $this->assertDatabaseHas('service_order_items', ['id' => $item->id]);
    }

    public function test_validation_rejects_missing_quantity_and_price(): void
    {
        $order = ServiceOrder::factory()->inProgress()->create();

        $this->actingAs($this->employee())
            ->post(route('workshop.items.store', $order), [
                'description' => 'Sans quantité',
            ])
            ->assertSessionHasErrors(['quantity', 'unit_price_ht']);
    }

    public function test_free_text_line_requires_a_description(): void
    {
        $order = ServiceOrder::factory()->inProgress()->create();

        $this->actingAs($this->employee())
            ->post(route('workshop.items.store', $order), [
                'quantity'      => 1,
                'unit_price_ht' => 100,
            ])
            ->assertSessionHasErrors('description');
    }

    public function test_guest_cannot_add_parts(): void
    {
        $order = ServiceOrder::factory()->inProgress()->create();

        $this->post(route('workshop.items.store', $order), [
            'description'   => 'Test',
            'quantity'      => 1,
            'unit_price_ht' => 100,
        ])->assertRedirect(route('login'));
    }

    public function test_user_without_role_cannot_add_parts(): void
    {
        $order = ServiceOrder::factory()->inProgress()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('workshop.items.store', $order), [
                'description'   => 'Test',
                'quantity'      => 1,
                'unit_price_ht' => 100,
            ])
            ->assertForbidden();
    }
}

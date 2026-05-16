<?php

namespace Tests\Feature;

use App\Enums\ProductCategory;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Guests
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('products.create'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_show(): void
    {
        $product = Product::factory()->create();
        $this->get(route('products.show', $product))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_edit(): void
    {
        $product = Product::factory()->create();
        $this->get(route('products.edit', $product))->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Employee — view only
    // -------------------------------------------------------------------------

    public function test_employee_can_access_index(): void
    {
        $employee = User::factory()->create()->assignRole('employee');

        $this->actingAs($employee)
            ->get(route('products.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Products/Index'));
    }

    public function test_employee_can_access_show(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $product  = Product::factory()->create();

        $this->actingAs($employee)
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Products/Show')
                ->where('product.id', $product->id)
                ->where('can.edit', false)
                ->where('can.delete', false)
            );
    }

    public function test_employee_cannot_access_create(): void
    {
        $employee = User::factory()->create()->assignRole('employee');

        $this->actingAs($employee)
            ->get(route('products.create'))
            ->assertForbidden();
    }

    public function test_employee_cannot_store(): void
    {
        $employee = User::factory()->create()->assignRole('employee');

        $this->actingAs($employee)
            ->post(route('products.store'), [])
            ->assertForbidden();
    }

    public function test_employee_cannot_access_edit(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $product  = Product::factory()->create();

        $this->actingAs($employee)
            ->get(route('products.edit', $product))
            ->assertForbidden();
    }

    public function test_employee_cannot_update(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $product  = Product::factory()->create();

        $this->actingAs($employee)
            ->put(route('products.update', $product), [])
            ->assertForbidden();
    }

    public function test_employee_cannot_delete(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $product  = Product::factory()->create();

        $this->actingAs($employee)
            ->delete(route('products.destroy', $product))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Admin — full access
    // -------------------------------------------------------------------------

    public function test_admin_can_access_index(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('products.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Products/Index')
                ->where('can.create', true)
            );
    }

    public function test_admin_can_access_create(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('products.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Products/Create'));
    }

    public function test_admin_can_store_product(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $payload = [
            'category'            => ProductCategory::Huile->value,
            'name'                => 'Huile 5W-30 1L',
            'sku'                 => 'TEST-SKU-001',
            'unit'                => 'L',
            'price_sell'          => 55.00,
            'quantity_on_hand'    => 10,
            'low_stock_threshold' => 3,
        ];

        $this->actingAs($admin)
            ->post(route('products.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['sku' => 'TEST-SKU-001', 'name' => 'Huile 5W-30 1L']);
    }

    public function test_admin_can_access_edit(): void
    {
        $admin   = User::factory()->create()->assignRole('admin');
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->get(route('products.edit', $product))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Products/Edit')
                ->where('product.id', $product->id)
            );
    }

    public function test_admin_can_update_product(): void
    {
        $admin   = User::factory()->create()->assignRole('admin');
        $product = Product::factory()->create();

        $payload = [
            'category'            => $product->category->value,
            'name'                => 'Nouveau nom',
            'unit'                => $product->unit,
            'price_sell'          => $product->price_sell,
            'quantity_on_hand'    => $product->quantity_on_hand,
            'low_stock_threshold' => $product->low_stock_threshold,
        ];

        $this->actingAs($admin)
            ->put(route('products.update', $product), $payload)
            ->assertRedirect(route('products.show', $product));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Nouveau nom']);
    }

    public function test_admin_can_delete_product(): void
    {
        $admin   = User::factory()->create()->assignRole('admin');
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    // -------------------------------------------------------------------------
    // Index — category filter
    // -------------------------------------------------------------------------

    public function test_index_filters_by_category(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        Product::factory()->create(['category' => ProductCategory::Huile->value]);
        Product::factory()->create(['category' => ProductCategory::Filtre->value]);

        $this->actingAs($admin)
            ->get(route('products.index', ['category' => ProductCategory::Huile->value]))
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Products/Index')
                ->where('products.total', 1)
                ->where('products.data.0.category', ProductCategory::Huile->value)
            );
    }

    public function test_index_ignores_invalid_category_filter(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        Product::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('products.index', ['category' => 'invalide']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('products.total', 3)
            );
    }

    // -------------------------------------------------------------------------
    // Index — low_stock filter
    // -------------------------------------------------------------------------

    public function test_index_filters_low_stock_products(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        Product::factory()->create(['quantity_on_hand' => 2, 'low_stock_threshold' => 5]);
        Product::factory()->create(['quantity_on_hand' => 10, 'low_stock_threshold' => 5]);

        $this->actingAs($admin)
            ->get(route('products.index', ['low_stock' => '1']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('products.total', 1)
                ->where('products.data.0.is_low_stock', true)
            );
    }
}

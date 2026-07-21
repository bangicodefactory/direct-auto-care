<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServicesControllerTest extends TestCase
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

    private function employee(): User
    {
        return User::factory()->create()->assignRole('employee');
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'             => 'Vidange',
            'description'      => 'Vidange huile moteur',
            'default_price_ht' => 150,
            'is_active'        => true,
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Guests
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('services.index'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('services.create'))->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Employee — view only
    // -------------------------------------------------------------------------

    public function test_employee_can_access_index(): void
    {
        $this->actingAs($this->employee())
            ->get(route('services.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Services/Index')
                ->where('can.create', false)
                ->where('can.edit', false)
                ->where('can.delete', false));
    }

    public function test_employee_cannot_access_create(): void
    {
        $this->actingAs($this->employee())
            ->get(route('services.create'))
            ->assertForbidden();
    }

    public function test_employee_cannot_store(): void
    {
        $this->actingAs($this->employee())
            ->post(route('services.store'), $this->payload())
            ->assertForbidden();
    }

    public function test_employee_cannot_update(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->employee())
            ->put(route('services.update', $service), $this->payload())
            ->assertForbidden();
    }

    public function test_employee_cannot_delete(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->employee())
            ->delete(route('services.destroy', $service))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Admin — full CRUD
    // -------------------------------------------------------------------------

    public function test_admin_can_store_service(): void
    {
        $this->actingAs($this->admin())
            ->post(route('services.store'), $this->payload())
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('services', [
            'name'             => 'Vidange',
            'default_price_ht' => 150,
            'is_active'        => 1,
        ]);
    }

    public function test_admin_can_update_service(): void
    {
        $service = Service::factory()->create(['name' => 'Vidange', 'default_price_ht' => 150]);

        $this->actingAs($this->admin())
            ->put(route('services.update', $service), $this->payload([
                'name'             => 'Vidange complète',
                'default_price_ht' => 220,
                'is_active'        => false,
            ]))
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('services', [
            'id'               => $service->id,
            'name'             => 'Vidange complète',
            'default_price_ht' => 220,
            'is_active'        => 0,
        ]);
    }

    public function test_admin_can_soft_delete_service(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('services.destroy', $service))
            ->assertRedirect(route('services.index'));

        $this->assertSoftDeleted('services', ['id' => $service->id]);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function test_store_rejects_duplicate_name(): void
    {
        Service::factory()->create(['name' => 'Vidange']);

        $this->actingAs($this->admin())
            ->post(route('services.store'), $this->payload())
            ->assertSessionHasErrors('name');
    }

    public function test_store_accepts_name_of_a_soft_deleted_service(): void
    {
        Service::factory()->create(['name' => 'Vidange'])->delete();

        $this->actingAs($this->admin())
            ->post(route('services.store'), $this->payload())
            ->assertRedirect(route('services.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Service::where('name', 'Vidange')->count());
    }

    public function test_update_allows_keeping_own_name(): void
    {
        $service = Service::factory()->create(['name' => 'Vidange']);

        $this->actingAs($this->admin())
            ->put(route('services.update', $service), $this->payload())
            ->assertSessionHasNoErrors();
    }

    public function test_store_requires_default_price(): void
    {
        $this->actingAs($this->admin())
            ->post(route('services.store'), $this->payload(['default_price_ht' => null]))
            ->assertSessionHasErrors('default_price_ht');
    }

    // -------------------------------------------------------------------------
    // Index filters
    // -------------------------------------------------------------------------

    public function test_index_can_filter_active_only(): void
    {
        Service::factory()->create(['name' => 'Vidange']);
        Service::factory()->inactive()->create(['name' => 'Ancien service']);

        $this->actingAs($this->admin())
            ->get(route('services.index', ['active_only' => 1]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Services/Index')
                ->has('services.data', 1)
                ->where('services.data.0.name', 'Vidange'));
    }
}

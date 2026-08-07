<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomersControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->employee = User::factory()->create();
        $this->employee->assignRole('employee');
    }

    // -------------------------------------------------------------------------
    // index — search
    // -------------------------------------------------------------------------

    public function test_index_returns_all_customers_without_search(): void
    {
        Customer::factory(3)->create();

        $this->actingAs($this->employee)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Customers/Index')
                ->has('customers.data', 3)
            );
    }

    public function test_index_filters_by_first_name(): void
    {
        Customer::factory()->create(['first_name' => 'Youssef', 'last_name' => 'Alaoui']);
        Customer::factory()->create(['first_name' => 'Sara', 'last_name' => 'Bennani']);

        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => 'Youssef']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.first_name', 'Youssef')
            );
    }

    public function test_index_filters_by_last_name(): void
    {
        Customer::factory()->create(['first_name' => 'Omar', 'last_name' => 'Bennani']);
        Customer::factory()->create(['first_name' => 'Sara', 'last_name' => 'Alaoui']);

        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => 'Bennani']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.last_name', 'Bennani')
            );
    }

    public function test_index_filters_by_phone(): void
    {
        Customer::factory()->create(['phone' => '+212611111111']);
        Customer::factory()->create(['phone' => '+212622222222']);

        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => '611111111']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.phone', '+212611111111')
            );
    }

    public function test_index_filters_by_email(): void
    {
        Customer::factory()->create(['first_name' => 'Youssef', 'last_name' => 'Alaoui', 'email' => 'youssef@example.com']);
        Customer::factory()->create(['first_name' => 'Sara', 'last_name' => 'Bennani', 'email' => 'sara@example.com']);

        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => 'youssef']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.email', 'youssef@example.com')
            );
    }

    public function test_index_returns_empty_when_no_match(): void
    {
        Customer::factory(2)->create();

        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => 'zzznomatch']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 0)
            );
    }

    // -------------------------------------------------------------------------
    // index — sort
    // -------------------------------------------------------------------------

    public function test_index_sorts_by_last_name_ascending_by_default(): void
    {
        Customer::factory()->create(['last_name' => 'Zouiten']);
        Customer::factory()->create(['last_name' => 'Alaoui']);
        Customer::factory()->create(['last_name' => 'Bennani']);

        $this->actingAs($this->employee)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('customers.data.0.last_name', 'Alaoui')
                ->where('customers.data.1.last_name', 'Bennani')
                ->where('customers.data.2.last_name', 'Zouiten')
            );
    }

    public function test_index_sort_direction_defaults_to_ascending(): void
    {
        // No direction param in request → filters.direction is absent (not sent)
        $this->actingAs($this->employee)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->missing('filters.direction')
            );
    }

    public function test_index_rejects_unknown_sort_field(): void
    {
        Customer::factory()->create(['last_name' => 'Zouiten']);
        Customer::factory()->create(['last_name' => 'Alaoui']);

        // Unknown sort field falls back to last_name ASC
        $this->actingAs($this->employee)
            ->get(route('customers.index', ['sort' => 'injected_field']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('customers.data.0.last_name', 'Alaoui')
            );
    }

    // -------------------------------------------------------------------------
    // index — pagination
    // -------------------------------------------------------------------------

    public function test_index_paginates_20_per_page(): void
    {
        Customer::factory(25)->create();

        $this->actingAs($this->employee)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 20)
                ->where('customers.total', 25)
            );
    }

    // -------------------------------------------------------------------------
    // index — can prop
    // -------------------------------------------------------------------------

    public function test_index_exposes_can_delete_false_for_employee(): void
    {
        Customer::factory()->create();

        $this->actingAs($this->employee)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('customers.data.0.can_delete', false)
            );
    }

    public function test_index_exposes_can_delete_true_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Customer::factory()->create();

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('customers.data.0.can_delete', true)
            );
    }

    public function test_index_marks_a_customer_holding_a_facture_as_undeletable(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $withFacture = Customer::factory()->create(['last_name' => 'Aaaa']);
        Customer::factory()->create(['last_name' => 'Bbbb']);
        Invoice::factory()->create([
            'customer_id' => $withFacture->id,
            'type'        => InvoiceType::Invoice->value,
        ]);

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('customers.data.0.can_delete', false)
                ->where('customers.data.1.can_delete', true)
            );
    }

    public function test_index_marks_a_customer_whose_facture_is_soft_deleted_as_undeletable(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = Customer::factory()->create();
        $facture  = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => InvoiceType::Invoice->value,
        ]);
        $facture->delete();

        // The listing preloads the count, so this pins that the preload keeps
        // counting trashed factures — they keep their fiscal number.
        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('customers.data.0.can_delete', false)
            );
    }

    public function test_index_still_allows_deleting_a_customer_with_only_a_devis(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = Customer::factory()->create();
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => InvoiceType::Quote->value,
        ]);

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('customers.data.0.can_delete', true)
            );
    }

    // -------------------------------------------------------------------------
    // index — filters echoed back
    // -------------------------------------------------------------------------

    public function test_index_echoes_filters_back_to_frontend(): void
    {
        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => 'Omar', 'sort' => 'email', 'direction' => 'desc']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('filters.search', 'Omar')
                ->where('filters.sort', 'email')
                ->where('filters.direction', 'desc')
            );
    }

    // -------------------------------------------------------------------------
    // show — eager-loads relationships
    // -------------------------------------------------------------------------

    public function test_show_loads_customer_with_relationships(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->employee)
            ->get(route('customers.show', $customer))
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Customers/Show')
                ->has('customer.vehicles')
                ->has('customer.invoices')
                ->has('customer.appointments')
            );
    }

    public function test_show_exposes_can_delete_false_for_employee(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->employee)
            ->get(route('customers.show', $customer))
            ->assertInertia(
                fn (Assert $page) => $page->where('can.delete', false)
            );
    }

    public function test_show_exposes_can_delete_true_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->get(route('customers.show', $customer))
            ->assertInertia(
                fn (Assert $page) => $page->where('can.delete', true)
            );
    }

    public function test_show_explains_why_a_customer_holding_factures_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $customer = Customer::factory()->create();
        Invoice::factory(2)->create([
            'customer_id' => $customer->id,
            'type'        => InvoiceType::Invoice->value,
        ]);

        $this->actingAs($admin)
            ->get(route('customers.show', $customer))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('can.delete', false)
                ->where('can.delete_blocked_reason', fn (string $reason) => str_contains($reason, '2 factures'))
            );
    }

    public function test_show_reason_is_singular_for_a_single_facture(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $customer = Customer::factory()->create();
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => InvoiceType::Invoice->value,
        ]);

        $this->actingAs($admin)
            ->get(route('customers.show', $customer))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('can.delete_blocked_reason', fn (string $reason) => str_contains($reason, '1 facture :'))
            );
    }

    public function test_show_has_no_blocked_reason_when_the_customer_has_no_facture(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->employee)
            ->get(route('customers.show', $customer))
            ->assertInertia(
                fn (Assert $page) => $page->where('can.delete_blocked_reason', null)
            );
    }

    public function test_show_does_not_blame_factures_when_the_user_simply_lacks_the_role(): void
    {
        // An employee is refused for their role, not for the factures. Telling
        // them otherwise would explain a restriction that is not theirs.
        $customer = Customer::factory()->create();
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => InvoiceType::Invoice->value,
        ]);

        $this->actingAs($this->employee)
            ->get(route('customers.show', $customer))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('can.delete', false)
                ->where('can.delete_blocked_reason', null)
            );
    }

    // -------------------------------------------------------------------------
    // create / store
    // -------------------------------------------------------------------------

    public function test_create_returns_create_component(): void
    {
        $this->actingAs($this->employee)
            ->get(route('customers.create'))
            ->assertInertia(
                fn (Assert $page) => $page->component('Customers/Create')
            );
    }

    public function test_store_creates_customer_and_redirects(): void
    {
        $response = $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0612345678',
                'email'      => 'youssef@example.com',
            ]);

        $customer = Customer::where('email', 'youssef@example.com')->firstOrFail();
        $response->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('customers', [
            'first_name' => 'Youssef',
            'last_name'  => 'Alaoui',
            'phone'      => '+212612345678',
            'email'      => 'youssef@example.com',
        ]);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->employee)
            ->post(route('customers.store'), [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'phone']);
    }

    public function test_store_creates_company_customer(): void
    {
        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'type'         => 'company',
                'company_name' => 'Transports Atlas SARL',
                'ice'          => '001234567000089',
                'if'           => '12345678',
                'ville'        => 'Casablanca',
                'phone'        => '0612345678',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'type'         => 'company',
            'company_name' => 'Transports Atlas SARL',
            'ice'          => '001234567000089',
            'if'           => '12345678',
            'ville'        => 'Casablanca',
            'phone'        => '+212612345678',
            'first_name'   => null,
            'last_name'    => null,
        ]);
    }

    public function test_store_company_requires_company_name(): void
    {
        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'type'  => 'company',
                'phone' => '0612345678',
            ])
            ->assertSessionHasErrors(['company_name']);
    }

    public function test_index_exposes_display_name_and_type_for_company(): void
    {
        Customer::factory()->company()->create(['company_name' => 'ACME SARL']);

        $this->actingAs($this->employee)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('customers.data.0.display_name', 'ACME SARL')
                ->where('customers.data.0.type', 'company')
            );
    }

    public function test_index_filters_by_company_name(): void
    {
        Customer::factory()->company()->create(['company_name' => 'Transports Atlas']);
        Customer::factory()->create(['first_name' => 'Sara', 'last_name' => 'Bennani']);

        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => 'Atlas']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.display_name', 'Transports Atlas')
            );
    }

    public function test_store_saves_notify_whatsapp_opt_out(): void
    {
        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name'      => 'Karim',
                'last_name'       => 'Tahiri',
                'phone'           => '0611111111',
                'notify_whatsapp' => false,
                'notify_email'    => true,
            ]);

        $this->assertDatabaseHas('customers', [
            'last_name'       => 'Tahiri',
            'notify_whatsapp' => false,
            'notify_email'    => true,
        ]);
    }

    public function test_store_saves_notify_email_opt_out(): void
    {
        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name'      => 'Nadia',
                'last_name'       => 'Chraibi',
                'phone'           => '0622222222',
                'email'           => 'nadia@example.com',
                'notify_whatsapp' => true,
                'notify_email'    => false,
            ]);

        $this->assertDatabaseHas('customers', [
            'last_name'       => 'Chraibi',
            'notify_whatsapp' => true,
            'notify_email'    => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // store — restoring a soft-deleted client
    // -------------------------------------------------------------------------

    public function test_store_restores_soft_deleted_customer_with_same_phone(): void
    {
        $deleted = Customer::factory()->create(['phone' => '+212611111111']);
        $deleted->delete();

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0611111111',
            ])
            ->assertRedirect(route('customers.show', $deleted->id));

        $this->assertNotSoftDeleted('customers', ['id' => $deleted->id]);
        $this->assertSame(1, Customer::withTrashed()->count());
    }

    public function test_store_restore_overwrites_details_with_submitted_values(): void
    {
        $deleted = Customer::factory()->create([
            'first_name' => 'Ancien',
            'last_name'  => 'Nom',
            'phone'      => '+212611111111',
            'email'      => 'ancien@example.com',
            'notes'      => 'Notes obsolètes',
        ]);
        $deleted->delete();

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0611111111',
                'email'      => 'youssef@example.com',
                'notes'      => 'Client revenu',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id'         => $deleted->id,
            'first_name' => 'Youssef',
            'last_name'  => 'Alaoui',
            'email'      => 'youssef@example.com',
            'notes'      => 'Client revenu',
            'deleted_at' => null,
        ]);
    }

    public function test_store_restore_keeps_the_customers_existing_vehicles(): void
    {
        $deleted = Customer::factory()->create(['phone' => '+212611111111']);
        $vehicle = Vehicle::factory()->create(['customer_id' => $deleted->id]);
        $deleted->delete();

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name'      => 'Youssef',
                'last_name'       => 'Alaoui',
                'phone'           => '0611111111',
                'confirm_restore' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($deleted->fresh()->vehicles->contains($vehicle));
    }

    public function test_store_requires_confirmation_when_the_trashed_client_has_history(): void
    {
        $deleted = Customer::factory()->create(['phone' => '+212611111111']);
        Vehicle::factory()->create(['customer_id' => $deleted->id]);
        $deleted->delete();

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0611111111',
            ])
            ->assertSessionHasErrors(['confirm_restore']);

        $this->assertSoftDeleted('customers', ['id' => $deleted->id]);
    }

    public function test_store_confirmation_message_names_the_client_and_their_history(): void
    {
        $deleted = Customer::factory()->create([
            'first_name' => 'Ancien',
            'last_name'  => 'Client',
            'phone'      => '+212611111111',
        ]);
        Vehicle::factory(2)->create(['customer_id' => $deleted->id]);
        $deleted->delete();

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0611111111',
            ])
            ->assertSessionHasErrors(['confirm_restore']);

        $message = session('errors')->first('confirm_restore');

        $this->assertStringContainsString('Ancien Client', $message);
        $this->assertStringContainsString('2 véhicules', $message);
    }

    public function test_store_restores_a_client_with_history_once_confirmed(): void
    {
        $deleted = Customer::factory()->create(['phone' => '+212611111111']);
        Vehicle::factory()->create(['customer_id' => $deleted->id]);
        $deleted->delete();

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name'      => 'Youssef',
                'last_name'       => 'Alaoui',
                'phone'           => '0611111111',
                'confirm_restore' => true,
            ])
            ->assertRedirect(route('customers.show', $deleted->id));

        $this->assertNotSoftDeleted('customers', ['id' => $deleted->id]);
    }

    public function test_store_requires_confirmation_when_the_history_is_itself_soft_deleted(): void
    {
        $deleted = Customer::factory()->create(['phone' => '+212611111111']);
        $vehicle = Vehicle::factory()->create(['customer_id' => $deleted->id]);
        $vehicle->delete();
        $deleted->delete();

        // The vehicle no longer shows on the client's page, but it stays
        // attached and service records resolve it with withTrashed().
        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0611111111',
            ])
            ->assertSessionHasErrors(['confirm_restore']);

        $this->assertSoftDeleted('customers', ['id' => $deleted->id]);
    }

    public function test_store_restores_a_client_without_history_without_confirmation(): void
    {
        $deleted = Customer::factory()->create(['phone' => '+212611111111']);
        $deleted->delete();

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0611111111',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted('customers', ['id' => $deleted->id]);
    }

    public function test_store_restore_resets_fields_absent_from_the_payload(): void
    {
        $deleted = Customer::factory()->create([
            'phone'           => '+212611111111',
            'email'           => 'ancien@example.com',
            'notes'           => 'Notes obsolètes',
            'address'         => 'Ancienne adresse',
            'notify_whatsapp' => true,
            'notify_email'    => true,
        ]);
        $deleted->delete();

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0611111111',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id'              => $deleted->id,
            'email'           => null,
            'notes'           => null,
            'address'         => null,
            'notify_whatsapp' => false,
            'notify_email'    => false,
        ]);
    }

    public function test_store_restore_clears_fiscal_fields_when_a_company_returns_as_individual(): void
    {
        $deleted = Customer::factory()->company()->create([
            'phone'        => '+212611111111',
            'company_name' => 'Transports Atlas SARL',
            'ice'          => '001234567000089',
            'if'           => '12345678',
            'patente'      => '87654321',
            'ville'        => 'Casablanca',
        ]);
        $deleted->delete();

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'type'       => 'individual',
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0611111111',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id'           => $deleted->id,
            'type'         => 'individual',
            'first_name'   => 'Youssef',
            'company_name' => null,
            'ice'          => null,
            'if'           => null,
            'patente'      => null,
            'ville'        => null,
            'deleted_at'   => null,
        ]);
    }

    public function test_store_is_forbidden_for_a_user_without_a_role(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0611111111',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_store_restore_flashes_a_restored_message_not_a_created_one(): void
    {
        $deleted = Customer::factory()->create(['phone' => '+212611111111']);
        $deleted->delete();

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0611111111',
            ])
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'restauré'));
    }

    public function test_store_still_rejects_the_phone_of_an_active_customer(): void
    {
        Customer::factory()->create(['phone' => '+212611111111']);

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0611111111',
            ])
            ->assertSessionHasErrors(['phone']);
    }

    public function test_store_restore_rejects_an_email_held_by_another_customer(): void
    {
        $deleted = Customer::factory()->create([
            'phone' => '+212611111111',
            'email' => 'ancien@example.com',
        ]);
        $deleted->delete();
        Customer::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '0611111111',
                'email'      => 'taken@example.com',
            ])
            ->assertSessionHasErrors(['email']);

        $this->assertSoftDeleted('customers', ['id' => $deleted->id]);
    }

    public function test_store_creates_a_new_customer_when_a_different_client_is_trashed(): void
    {
        $deleted = Customer::factory()->create(['phone' => '+212611111111']);
        $deleted->delete();

        $this->actingAs($this->employee)
            ->post(route('customers.store'), [
                'first_name' => 'Sara',
                'last_name'  => 'Bennani',
                'phone'      => '0622222222',
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('customers', ['id' => $deleted->id]);
        $this->assertDatabaseHas('customers', [
            'phone'      => '+212622222222',
            'last_name'  => 'Bennani',
            'deleted_at' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // edit / update
    // -------------------------------------------------------------------------

    public function test_edit_returns_edit_component_with_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->employee)
            ->get(route('customers.edit', $customer))
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Customers/Edit')
                ->where('customer.id', $customer->id)
            );
    }

    public function test_update_saves_changes_and_redirects(): void
    {
        $customer = Customer::factory()->create(['phone' => '+212611111111']);

        $this->actingAs($this->employee)
            ->put(route('customers.update', $customer), [
                'first_name' => 'Sara',
                'last_name'  => 'Bennani',
                'phone'      => '0622222222',
            ])
            ->assertRedirect(route('customers.show', $customer->id));

        $this->assertDatabaseHas('customers', [
            'id'         => $customer->id,
            'first_name' => 'Sara',
            'last_name'  => 'Bennani',
            'phone'      => '+212622222222',
        ]);
    }

    public function test_update_rejects_missing_required_fields(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->employee)
            ->put(route('customers.update', $customer), [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'phone']);
    }

    public function test_update_converting_company_to_individual_clears_fiscal_fields(): void
    {
        $customer = Customer::factory()->company()->create([
            'company_name' => 'ACME SARL',
            'ice'          => '001234567000089',
            'if'           => '12345678',
            'patente'      => '87654321',
            'ville'        => 'Casablanca',
        ]);

        $this->actingAs($this->employee)
            ->put(route('customers.update', $customer), [
                'type'       => 'individual',
                'first_name' => 'Sara',
                'last_name'  => 'Bennani',
                'phone'      => $customer->phone,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id'           => $customer->id,
            'type'         => 'individual',
            'first_name'   => 'Sara',
            'company_name' => null,
            'ice'          => null,
            'if'           => null,
            'patente'      => null,
            'ville'        => null,
        ]);
    }

    public function test_update_saves_notify_toggles(): void
    {
        $customer = Customer::factory()->create([
            'notify_whatsapp' => true,
            'notify_email'    => true,
        ]);

        $this->actingAs($this->employee)
            ->put(route('customers.update', $customer), [
                'first_name'      => $customer->first_name,
                'last_name'       => $customer->last_name,
                'phone'           => $customer->phone,
                'notify_whatsapp' => false,
                'notify_email'    => false,
            ]);

        $this->assertDatabaseHas('customers', [
            'id'              => $customer->id,
            'notify_whatsapp' => false,
            'notify_email'    => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // destroy — authorization
    // -------------------------------------------------------------------------

    public function test_employee_cannot_delete_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->employee)
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();
    }

    public function test_admin_can_delete_customer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }
}

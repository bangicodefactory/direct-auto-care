<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleNote;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * "Notes flotte": per-vehicle notes logbook, limited to fleet clients' vehicles.
 */
class FleetNotesTest extends TestCase
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

    private function fleetVehicle(array $attributes = []): Vehicle
    {
        return Vehicle::factory()
            ->for(Customer::factory()->company()->fleetNotes())
            ->create($attributes);
    }

    private function regularVehicle(): Vehicle
    {
        return Vehicle::factory()->for(Customer::factory())->create();
    }

    // -------------------------------------------------------------------------
    // Access
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('fleet-notes.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_role_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('fleet-notes.index'))
            ->assertForbidden();
    }

    public function test_index_lists_only_fleet_vehicles(): void
    {
        $fleet = $this->fleetVehicle(['license_plate' => '12345-A-1']);
        $this->regularVehicle();

        $this->actingAs($this->employee())
            ->get(route('fleet-notes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('FleetNotes/Index')
                ->has('vehicles.data', 1)
                ->where('vehicles.data.0.id', $fleet->id)
                ->where('vehicles.data.0.license_plate', '12345-A-1')
                ->has('customers', 1));
    }

    public function test_index_shows_note_count_latest_note_and_in_shop_first(): void
    {
        $idle = $this->fleetVehicle(['license_plate' => 'A-0001']);
        $inShop = $this->fleetVehicle(['license_plate' => 'Z-9999']);

        ServiceOrder::factory()->inProgress()->create([
            'customer_id' => $inShop->customer_id,
            'vehicle_id'  => $inShop->id,
        ]);
        ServiceOrder::factory()->closed()->create([
            'customer_id' => $idle->customer_id,
            'vehicle_id'  => $idle->id,
        ]);

        VehicleNote::factory()->for($idle)->create(['body' => 'Ancienne note', 'created_at' => now()->subDay()]);
        VehicleNote::factory()->for($idle)->create(['body' => 'Dernière note']);

        $this->actingAs($this->employee())
            ->get(route('fleet-notes.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('vehicles.data.0.id', $inShop->id)
                ->where('vehicles.data.0.in_shop', true)
                ->where('vehicles.data.0.notes_count', 0)
                ->where('vehicles.data.0.latest_note', null)
                ->where('vehicles.data.1.id', $idle->id)
                ->where('vehicles.data.1.in_shop', false)
                ->where('vehicles.data.1.notes_count', 2)
                ->where('vehicles.data.1.latest_note.excerpt', 'Dernière note'));
    }

    public function test_index_search_filters_by_plate(): void
    {
        $match = $this->fleetVehicle(['license_plate' => '777-B-12']);
        $this->fleetVehicle(['license_plate' => '111-C-34']);

        $this->actingAs($this->admin())
            ->get(route('fleet-notes.index', ['search' => '777']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('vehicles.data', 1)
                ->where('vehicles.data.0.id', $match->id));
    }

    public function test_show_renders_timeline_newest_first(): void
    {
        $vehicle = $this->fleetVehicle();
        $author = $this->employee();
        VehicleNote::factory()->for($vehicle)->for($author)->create(['body' => 'Première', 'created_at' => now()->subHour()]);
        VehicleNote::factory()->for($vehicle)->for($author)->create(['body' => 'Seconde']);

        $this->actingAs($author)
            ->get(route('fleet-notes.show', $vehicle))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('FleetNotes/Show')
                ->where('vehicle.id', $vehicle->id)
                ->where('notes.0.body', 'Seconde')
                ->where('notes.0.author', $author->name)
                ->where('notes.0.can.update', true)
                ->where('notes.1.body', 'Première')
                ->where('can.create', true));
    }

    public function test_show_returns_404_for_a_non_fleet_vehicle(): void
    {
        $this->actingAs($this->admin())
            ->get(route('fleet-notes.show', $this->regularVehicle()))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function test_employee_can_add_a_note(): void
    {
        $vehicle = $this->fleetVehicle();
        $employee = $this->employee();

        $this->actingAs($employee)
            ->post(route('fleet-notes.notes.store', $vehicle), ['body' => 'Bruit au freinage signalé.'])
            ->assertRedirect(route('fleet-notes.show', $vehicle))
            ->assertSessionHas('success', 'Note ajoutée.');

        $this->assertDatabaseHas('vehicle_notes', [
            'vehicle_id' => $vehicle->id,
            'user_id'    => $employee->id,
            'body'       => 'Bruit au freinage signalé.',
        ]);
    }

    public function test_cannot_add_a_note_on_a_non_fleet_vehicle(): void
    {
        $vehicle = $this->regularVehicle();

        $this->actingAs($this->admin())
            ->post(route('fleet-notes.notes.store', $vehicle), ['body' => 'Test'])
            ->assertForbidden();

        $this->assertDatabaseCount('vehicle_notes', 0);
    }

    public function test_empty_note_is_rejected_in_french(): void
    {
        $this->actingAs($this->employee())
            ->post(route('fleet-notes.notes.store', $this->fleetVehicle()), ['body' => ''])
            ->assertSessionHasErrors(['body' => 'La note ne peut pas être vide.']);
    }

    public function test_too_long_note_is_rejected_in_french(): void
    {
        $this->actingAs($this->employee())
            ->post(route('fleet-notes.notes.store', $this->fleetVehicle()), ['body' => str_repeat('a', 5001)])
            ->assertSessionHasErrors(['body' => 'La note ne peut pas dépasser 5000 caractères.']);
    }

    // -------------------------------------------------------------------------
    // Update / delete — admin or author only
    // -------------------------------------------------------------------------

    public function test_author_can_edit_their_note(): void
    {
        $author = $this->employee();
        $note = VehicleNote::factory()->for($this->fleetVehicle())->for($author)->create();

        $this->actingAs($author)
            ->put(route('vehicle-notes.update', $note), ['body' => 'Corrigé'])
            ->assertRedirect(route('fleet-notes.show', $note->vehicle_id));

        $this->assertSame('Corrigé', $note->fresh()->body);
    }

    public function test_other_employee_cannot_edit_or_delete_a_note(): void
    {
        $note = VehicleNote::factory()->for($this->fleetVehicle())->for($this->employee())->create(['body' => 'Original']);
        $other = $this->employee();

        $this->actingAs($other)
            ->put(route('vehicle-notes.update', $note), ['body' => 'Piraté'])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete(route('vehicle-notes.destroy', $note))
            ->assertForbidden();

        $this->assertSame('Original', $note->fresh()->body);
    }

    public function test_other_employee_sees_no_edit_rights_in_timeline(): void
    {
        $vehicle = $this->fleetVehicle();
        VehicleNote::factory()->for($vehicle)->for($this->employee())->create();

        $this->actingAs($this->employee())
            ->get(route('fleet-notes.show', $vehicle))
            ->assertInertia(fn (Assert $page) => $page
                ->where('notes.0.can.update', false)
                ->where('notes.0.can.delete', false));
    }

    public function test_admin_can_edit_and_delete_any_note(): void
    {
        $admin = $this->admin();
        $note = VehicleNote::factory()->for($this->fleetVehicle())->for($this->employee())->create();

        $this->actingAs($admin)
            ->put(route('vehicle-notes.update', $note), ['body' => 'Revu par l’admin'])
            ->assertRedirect();
        $this->assertSame('Revu par l’admin', $note->fresh()->body);

        $this->actingAs($admin)
            ->delete(route('vehicle-notes.destroy', $note))
            ->assertRedirect(route('fleet-notes.show', $note->vehicle_id))
            ->assertSessionHas('success', 'Note supprimée.');
        $this->assertModelMissing($note);
    }

    public function test_author_can_delete_their_note(): void
    {
        $author = $this->employee();
        $note = VehicleNote::factory()->for($this->fleetVehicle())->for($author)->create();

        $this->actingAs($author)
            ->delete(route('vehicle-notes.destroy', $note))
            ->assertRedirect();

        $this->assertModelMissing($note);
    }

    // -------------------------------------------------------------------------
    // Fleet flag on the customer — admin only
    // -------------------------------------------------------------------------

    private function customerPayload(Customer $customer, bool $fleet): array
    {
        return [
            'type'                => 'company',
            'company_name'        => $customer->company_name,
            'phone'               => $customer->phone,
            'fleet_notes_enabled' => $fleet,
        ];
    }

    public function test_admin_can_flag_a_customer_as_fleet_client(): void
    {
        $customer = Customer::factory()->company()->create();

        $this->actingAs($this->admin())
            ->put(route('customers.update', $customer), $this->customerPayload($customer, true))
            ->assertRedirect(route('customers.show', $customer));

        $this->assertTrue($customer->fresh()->fleet_notes_enabled);
    }

    public function test_employee_cannot_change_the_fleet_flag(): void
    {
        $customer = Customer::factory()->company()->create();

        $this->actingAs($this->employee())
            ->put(route('customers.update', $customer), $this->customerPayload($customer, true))
            ->assertRedirect(route('customers.show', $customer));

        $this->assertFalse($customer->fresh()->fleet_notes_enabled);
    }

    public function test_edit_page_exposes_the_fleet_toggle_only_to_admins(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('customers.edit', $customer))
            ->assertInertia(fn (Assert $page) => $page->where('can.manage_fleet_notes', true));

        $this->actingAs($this->employee())
            ->get(route('customers.edit', $customer))
            ->assertInertia(fn (Assert $page) => $page->where('can.manage_fleet_notes', false));
    }

    public function test_vehicle_page_exposes_fleet_notes_count_only_for_fleet_vehicles(): void
    {
        $fleet = $this->fleetVehicle();
        VehicleNote::factory()->count(2)->for($fleet)->create();
        $employee = $this->employee();

        $this->actingAs($employee)
            ->get(route('vehicles.show', $fleet))
            ->assertInertia(fn (Assert $page) => $page->where('vehicle.fleet_notes_count', 2));

        $this->actingAs($employee)
            ->get(route('vehicles.show', $this->regularVehicle()))
            ->assertInertia(fn (Assert $page) => $page->where('vehicle.fleet_notes_count', null));
    }

    // -------------------------------------------------------------------------
    // Menu visibility
    // -------------------------------------------------------------------------

    public function test_menu_flag_is_off_without_a_fleet_client(): void
    {
        Customer::factory()->create();

        $this->actingAs($this->employee())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('fleetNotesEnabled', false));
    }

    public function test_menu_flag_is_on_once_a_client_is_flagged(): void
    {
        Customer::factory()->fleetNotes()->create();

        $this->actingAs($this->employee())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('fleetNotesEnabled', true));
    }
}

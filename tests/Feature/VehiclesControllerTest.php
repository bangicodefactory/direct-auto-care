<?php

namespace Tests\Feature;

use App\Enums\FuelType;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VehiclesControllerTest extends TestCase
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
    // guest — redirected to login
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('vehicles.index'))
            ->assertRedirect(route('login'));
    }

    private function validPayload(Customer $customer): array
    {
        return [
            'customer_id'   => $customer->id,
            'make'          => 'Toyota',
            'model'         => 'Corolla',
            'year'          => 2020,
            'license_plate' => '12345-A-01',
            'mileage'       => 0,
            'fuel_type'     => FuelType::Essence->value,
        ];
    }

    // -------------------------------------------------------------------------
    // index — search
    // -------------------------------------------------------------------------

    public function test_index_returns_all_vehicles_without_search(): void
    {
        Vehicle::factory(3)->create();

        $this->actingAs($this->employee)
            ->get(route('vehicles.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Vehicles/Index')
                ->has('vehicles.data', 3)
            );
    }

    public function test_index_filters_by_license_plate(): void
    {
        $customer = Customer::factory()->create();
        Vehicle::factory()->create(['license_plate' => '11111-A-01', 'customer_id' => $customer->id]);
        Vehicle::factory()->create(['license_plate' => '22222-B-02', 'customer_id' => $customer->id]);

        $this->actingAs($this->employee)
            ->get(route('vehicles.index', ['search' => '11111']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('vehicles.data', 1)
                ->where('vehicles.data.0.license_plate', '11111-A-01')
            );
    }

    public function test_index_filters_by_customer_first_name(): void
    {
        $youssef = Customer::factory()->create(['first_name' => 'Youssef', 'last_name' => 'Alaoui']);
        $sara    = Customer::factory()->create(['first_name' => 'Sara', 'last_name' => 'Bennani']);
        Vehicle::factory()->create(['customer_id' => $youssef->id]);
        Vehicle::factory()->create(['customer_id' => $sara->id]);

        $this->actingAs($this->employee)
            ->get(route('vehicles.index', ['search' => 'Youssef']))
            ->assertInertia(
                fn (Assert $page) => $page->has('vehicles.data', 1)
            );
    }

    public function test_index_filters_by_customer_last_name(): void
    {
        $alaoui  = Customer::factory()->create(['first_name' => 'Omar', 'last_name' => 'Alaoui']);
        $bennani = Customer::factory()->create(['first_name' => 'Sara', 'last_name' => 'Bennani']);
        Vehicle::factory()->create(['customer_id' => $alaoui->id]);
        Vehicle::factory()->create(['customer_id' => $bennani->id]);

        $this->actingAs($this->employee)
            ->get(route('vehicles.index', ['search' => 'Alaoui']))
            ->assertInertia(
                fn (Assert $page) => $page->has('vehicles.data', 1)
            );
    }

    public function test_index_returns_empty_when_no_match(): void
    {
        Vehicle::factory(2)->create();

        $this->actingAs($this->employee)
            ->get(route('vehicles.index', ['search' => 'zzznomatch']))
            ->assertInertia(
                fn (Assert $page) => $page->has('vehicles.data', 0)
            );
    }

    // -------------------------------------------------------------------------
    // index — pagination
    // -------------------------------------------------------------------------

    public function test_index_paginates_20_per_page(): void
    {
        Vehicle::factory(25)->create();

        $this->actingAs($this->employee)
            ->get(route('vehicles.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('vehicles.data', 20)
                ->where('vehicles.total', 25)
            );
    }

    // -------------------------------------------------------------------------
    // index — can prop
    // -------------------------------------------------------------------------

    public function test_index_exposes_can_delete_false_for_employee(): void
    {
        $this->actingAs($this->employee)
            ->get(route('vehicles.index'))
            ->assertInertia(
                fn (Assert $page) => $page->where('can.delete', false)
            );
    }

    public function test_index_exposes_can_delete_true_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('vehicles.index'))
            ->assertInertia(
                fn (Assert $page) => $page->where('can.delete', true)
            );
    }

    // -------------------------------------------------------------------------
    // index — filters echoed back
    // -------------------------------------------------------------------------

    public function test_index_echoes_search_filter_back(): void
    {
        $this->actingAs($this->employee)
            ->get(route('vehicles.index', ['search' => '12345']))
            ->assertInertia(
                fn (Assert $page) => $page->where('filters.search', '12345')
            );
    }

    // -------------------------------------------------------------------------
    // index — sort
    // -------------------------------------------------------------------------

    public function test_index_sorts_by_make_ascending(): void
    {
        $customer = Customer::factory()->create();
        Vehicle::factory()->create(['make' => 'Toyota', 'customer_id' => $customer->id]);
        Vehicle::factory()->create(['make' => 'Renault', 'customer_id' => $customer->id]);
        Vehicle::factory()->create(['make' => 'Peugeot', 'customer_id' => $customer->id]);

        $this->actingAs($this->employee)
            ->get(route('vehicles.index', ['sort' => 'make', 'direction' => 'asc']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('vehicles.data.0.make', 'Peugeot')
                ->where('vehicles.data.1.make', 'Renault')
                ->where('vehicles.data.2.make', 'Toyota')
            );
    }

    public function test_index_sorts_by_year_descending(): void
    {
        $customer = Customer::factory()->create();
        Vehicle::factory()->create(['year' => 2018, 'customer_id' => $customer->id]);
        Vehicle::factory()->create(['year' => 2022, 'customer_id' => $customer->id]);
        Vehicle::factory()->create(['year' => 2020, 'customer_id' => $customer->id]);

        $this->actingAs($this->employee)
            ->get(route('vehicles.index', ['sort' => 'year', 'direction' => 'desc']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('vehicles.data.0.year', 2022)
                ->where('vehicles.data.1.year', 2020)
                ->where('vehicles.data.2.year', 2018)
            );
    }

    public function test_index_echoes_sort_and_direction_filters_back(): void
    {
        $this->actingAs($this->employee)
            ->get(route('vehicles.index', ['sort' => 'make', 'direction' => 'desc']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('filters.sort', 'make')
                ->where('filters.direction', 'desc')
            );
    }

    public function test_index_ignores_invalid_sort_column(): void
    {
        Vehicle::factory(2)->create();

        $this->actingAs($this->employee)
            ->get(route('vehicles.index', ['sort' => 'fuel_type']))
            ->assertInertia(
                fn (Assert $page) => $page->has('vehicles.data', 2)
            );
    }

    // -------------------------------------------------------------------------
    // standalone create / store
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_standalone_create(): void
    {
        $this->get(route('vehicles.create'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_standalone_store(): void
    {
        $this->post(route('vehicles.store'), [])
            ->assertRedirect(route('login'));
    }

    public function test_standalone_create_returns_create_component_with_customers_list(): void
    {
        Customer::factory(3)->create();

        $this->actingAs($this->employee)
            ->get(route('vehicles.create'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Vehicles/Create')
                ->has('customers', 3)
            );
    }

    public function test_standalone_store_creates_vehicle_and_redirects_to_show(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->employee)
            ->post(route('vehicles.store'), $this->validPayload($customer));

        $vehicle = Vehicle::where('license_plate', '12345-A-01')->firstOrFail();
        $response->assertRedirect(route('vehicles.show', $vehicle));

        $this->assertDatabaseHas('vehicles', [
            'customer_id'   => $customer->id,
            'license_plate' => '12345-A-01',
        ]);
    }

    public function test_standalone_store_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->employee)
            ->post(route('vehicles.store'), [])
            ->assertSessionHasErrors(['customer_id', 'make', 'model', 'year', 'license_plate', 'fuel_type']);
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_create_returns_create_component_with_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->employee)
            ->get(route('customers.vehicles.create', $customer))
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Vehicles/Create')
                ->where('customer.id', $customer->id)
            );
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_vehicle_and_redirects_to_show(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->employee)
            ->post(route('customers.vehicles.store', $customer), $this->validPayload($customer));

        $vehicle = Vehicle::where('license_plate', '12345-A-01')->firstOrFail();
        $response->assertRedirect(route('vehicles.show', $vehicle));

        $this->assertDatabaseHas('vehicles', [
            'customer_id'   => $customer->id,
            'make'          => 'Toyota',
            'model'         => 'Corolla',
            'license_plate' => '12345-A-01',
        ]);
    }

    public function test_store_ignores_tampered_customer_id_and_uses_route_customer(): void
    {
        $customer  = Customer::factory()->create();
        $other     = Customer::factory()->create();
        $payload   = array_merge($this->validPayload($customer), ['customer_id' => $other->id]);

        $this->actingAs($this->employee)
            ->post(route('customers.vehicles.store', $customer), $payload);

        $vehicle = Vehicle::where('license_plate', '12345-A-01')->firstOrFail();
        $this->assertSame($customer->id, $vehicle->customer_id);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->employee)
            ->post(route('customers.vehicles.store', $customer), [])
            ->assertSessionHasErrors(['make', 'model', 'year', 'license_plate', 'fuel_type']);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_returns_show_component_with_vehicle_data(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->employee)
            ->get(route('vehicles.show', $vehicle))
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Vehicles/Show')
                ->where('vehicle.id', $vehicle->id)
                ->has('vehicle.customer')
                ->has('vehicle.service_records')
                ->has('vehicle.appointments')
            );
    }

    public function test_show_includes_service_records_newest_first(): void
    {
        $vehicle = Vehicle::factory()->create();
        ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'serviced_at' => now()->subDays(2)->toDateString()]);
        ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'serviced_at' => now()->subDay()->toDateString()]);

        $this->actingAs($this->employee)
            ->get(route('vehicles.show', $vehicle))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('vehicle.service_records', 2)
                ->where('vehicle.service_records.0.serviced_at', now()->subDay()->translatedFormat('d/m/Y'))
                ->where('vehicle.service_records.1.serviced_at', now()->subDays(2)->translatedFormat('d/m/Y'))
            );
    }

    public function test_show_includes_appointments_for_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();
        Appointment::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->actingAs($this->employee)
            ->get(route('vehicles.show', $vehicle))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('vehicle.appointments', 1)
            );
    }

    public function test_show_exposes_can_delete_false_for_employee(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->employee)
            ->get(route('vehicles.show', $vehicle))
            ->assertInertia(
                fn (Assert $page) => $page->where('can.delete', false)
            );
    }

    public function test_show_exposes_can_delete_true_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($admin)
            ->get(route('vehicles.show', $vehicle))
            ->assertInertia(
                fn (Assert $page) => $page->where('can.delete', true)
            );
    }

    // -------------------------------------------------------------------------
    // edit / update
    // -------------------------------------------------------------------------

    public function test_edit_returns_edit_component_with_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->employee)
            ->get(route('vehicles.edit', $vehicle))
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Vehicles/Edit')
                ->where('vehicle.id', $vehicle->id)
            );
    }

    public function test_update_saves_changes_and_redirects_to_show(): void
    {
        $vehicle = Vehicle::factory()->create(['mileage' => 10_000]);

        $this->actingAs($this->employee)
            ->put(route('vehicles.update', $vehicle), [
                'make'          => 'Honda',
                'model'         => 'Civic',
                'year'          => 2021,
                'license_plate' => $vehicle->license_plate,
                'mileage'       => 15_000,
                'fuel_type'     => FuelType::Diesel->value,
            ])
            ->assertRedirect(route('vehicles.show', $vehicle->id));

        $this->assertDatabaseHas('vehicles', [
            'id'    => $vehicle->id,
            'make'  => 'Honda',
            'model' => 'Civic',
            'year'  => 2021,
        ]);
    }

    public function test_update_rejects_missing_required_fields(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->employee)
            ->put(route('vehicles.update', $vehicle), [])
            ->assertSessionHasErrors(['make', 'model', 'year', 'license_plate', 'mileage', 'fuel_type']);
    }

    public function test_update_rejects_decreasing_mileage(): void
    {
        $vehicle = Vehicle::factory()->create(['mileage' => 50_000]);

        $this->actingAs($this->employee)
            ->put(route('vehicles.update', $vehicle), [
                'make'          => $vehicle->make,
                'model'         => $vehicle->model,
                'year'          => $vehicle->year,
                'license_plate' => $vehicle->license_plate,
                'mileage'       => 49_999,
                'fuel_type'     => $vehicle->fuel_type->value,
            ])
            ->assertSessionHasErrors('mileage');

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'mileage' => 50_000]);
    }

    // -------------------------------------------------------------------------
    // IST-32 — smoke: uniqueness (duplicate matricule)
    // -------------------------------------------------------------------------

    public function test_store_rejects_duplicate_license_plate_with_french_error(): void
    {
        $customer = Customer::factory()->create();
        Vehicle::factory()->create(['license_plate' => '12345-A-01', 'customer_id' => $customer->id]);

        $this->actingAs($this->employee)
            ->post(route('vehicles.store'), $this->validPayload($customer))
            ->assertSessionHasErrors([
                'license_plate' => "La valeur du champ plaque d'immatriculation est déjà utilisée.",
            ]);
    }

    public function test_update_rejects_duplicate_license_plate_of_another_vehicle(): void
    {
        $customer = Customer::factory()->create();
        Vehicle::factory()->create(['license_plate' => '99999-Z-99', 'customer_id' => $customer->id]);
        $vehicle = Vehicle::factory()->create(['license_plate' => '11111-A-01', 'customer_id' => $customer->id]);

        $this->actingAs($this->employee)
            ->put(route('vehicles.update', $vehicle), [
                'make'          => $vehicle->make,
                'model'         => $vehicle->model,
                'year'          => $vehicle->year,
                'license_plate' => '99999-Z-99',
                'mileage'       => $vehicle->mileage,
                'fuel_type'     => $vehicle->fuel_type->value,
            ])
            ->assertSessionHasErrors([
                'license_plate' => "La valeur du champ plaque d'immatriculation est déjà utilisée.",
            ]);
    }

    public function test_update_allows_same_license_plate_on_same_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['mileage' => 10_000]);

        $this->actingAs($this->employee)
            ->put(route('vehicles.update', $vehicle), [
                'make'          => $vehicle->make,
                'model'         => $vehicle->model,
                'year'          => $vehicle->year,
                'license_plate' => $vehicle->license_plate,
                'mileage'       => 10_000,
                'fuel_type'     => $vehicle->fuel_type->value,
            ])
            ->assertRedirect(route('vehicles.show', $vehicle->id));

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'mileage' => 10_000]);
    }

    // -------------------------------------------------------------------------
    // IST-32 — smoke: year out of range
    // -------------------------------------------------------------------------

    public function test_store_rejects_year_before_1950(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->employee)
            ->post(route('vehicles.store'), array_merge($this->validPayload($customer), ['year' => 1949]))
            ->assertSessionHasErrors('year');
    }

    public function test_store_rejects_year_after_next_year(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->employee)
            ->post(route('vehicles.store'), array_merge($this->validPayload($customer), ['year' => (int) date('Y') + 2]))
            ->assertSessionHasErrors('year');
    }

    public function test_update_rejects_year_before_1950(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->employee)
            ->put(route('vehicles.update', $vehicle), [
                'make'          => $vehicle->make,
                'model'         => $vehicle->model,
                'year'          => 1949,
                'license_plate' => $vehicle->license_plate,
                'mileage'       => $vehicle->mileage,
                'fuel_type'     => $vehicle->fuel_type->value,
            ])
            ->assertSessionHasErrors('year');
    }

    public function test_update_rejects_year_after_next_year(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->employee)
            ->put(route('vehicles.update', $vehicle), [
                'make'          => $vehicle->make,
                'model'         => $vehicle->model,
                'year'          => (int) date('Y') + 2,
                'license_plate' => $vehicle->license_plate,
                'mileage'       => $vehicle->mileage,
                'fuel_type'     => $vehicle->fuel_type->value,
            ])
            ->assertSessionHasErrors('year');
    }

    // -------------------------------------------------------------------------
    // IST-32 — smoke: mileage decrease French message
    // -------------------------------------------------------------------------

    public function test_mileage_decrease_produces_french_error_message(): void
    {
        $vehicle = Vehicle::factory()->create(['mileage' => 50_000]);

        $this->actingAs($this->employee)
            ->put(route('vehicles.update', $vehicle), [
                'make'          => $vehicle->make,
                'model'         => $vehicle->model,
                'year'          => $vehicle->year,
                'license_plate' => $vehicle->license_plate,
                'mileage'       => 49_999,
                'fuel_type'     => $vehicle->fuel_type->value,
            ])
            ->assertSessionHasErrors([
                'mileage' => 'Le kilométrage ne peut pas diminuer.',
            ]);
    }

    // -------------------------------------------------------------------------
    // destroy — authorization
    // -------------------------------------------------------------------------

    public function test_employee_cannot_delete_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->employee)
            ->delete(route('vehicles.destroy', $vehicle))
            ->assertForbidden();
    }

    public function test_admin_can_delete_vehicle(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($admin)
            ->delete(route('vehicles.destroy', $vehicle))
            ->assertRedirect(route('vehicles.index'));

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }
}

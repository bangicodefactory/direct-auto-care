<?php

namespace Tests\Feature;

use App\Models\ServiceRecord;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRecordsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private User $admin;
    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(ServiceTypeSeeder::class);

        $this->employee = User::factory()->create();
        $this->employee->assignRole('employee');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->vehicle = Vehicle::factory()->create(['mileage' => 10_000]);
    }

    private function validPayload(): array
    {
        return [
            'service_type_ids'   => [ServiceType::where('name', 'Vidange')->first()->id],
            'mileage_at_service' => 12_000,
            'notes'              => 'Vidange complète.',
            'serviced_at'        => now()->toDateString(),
        ];
    }

    // -------------------------------------------------------------------------
    // guest — redirected to login (HasMiddleware)
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('vehicles.service-records.create', $this->vehicle))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_store(): void
    {
        $this->post(route('vehicles.service-records.store', $this->vehicle), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_edit(): void
    {
        $record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->get(route('service-records.edit', $record))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_destroy(): void
    {
        $record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->delete(route('service-records.destroy', $record))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_employee_can_access_create_form(): void
    {
        $this->actingAs($this->employee)
            ->get(route('vehicles.service-records.create', $this->vehicle))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('ServiceRecords/Create'));
    }

    // -------------------------------------------------------------------------
    // store — happy path
    // -------------------------------------------------------------------------

    public function test_employee_can_store_service_record(): void
    {
        $this->actingAs($this->employee)
            ->post(route('vehicles.service-records.store', $this->vehicle), $this->validPayload())
            ->assertRedirect(route('vehicles.show', $this->vehicle));

        $this->assertDatabaseCount('service_records', 1);
        $this->assertDatabaseHas('service_records', [
            'vehicle_id'         => $this->vehicle->id,
            'mileage_at_service' => 12_000,
        ]);
    }

    public function test_store_syncs_service_types_pivot(): void
    {
        $typeId = ServiceType::where('name', 'Vidange')->first()->id;

        $this->actingAs($this->employee)
            ->post(route('vehicles.service-records.store', $this->vehicle), $this->validPayload());

        $record = ServiceRecord::first();
        $this->assertDatabaseHas('service_record_service_type', [
            'service_record_id' => $record->id,
            'service_type_id'   => $typeId,
        ]);
    }

    public function test_store_updates_vehicle_mileage_when_greater(): void
    {
        $this->actingAs($this->employee)
            ->post(route('vehicles.service-records.store', $this->vehicle), $this->validPayload());

        $this->assertSame(12_000, $this->vehicle->fresh()->mileage);
    }

    public function test_store_does_not_update_vehicle_mileage_when_equal_or_lower(): void
    {
        $payload                      = $this->validPayload();
        $payload['mileage_at_service'] = 10_000;

        $this->actingAs($this->employee)
            ->post(route('vehicles.service-records.store', $this->vehicle), $payload);

        $this->assertSame(10_000, $this->vehicle->fresh()->mileage);
    }

    public function test_store_allows_null_mileage_at_service(): void
    {
        $payload = $this->validPayload();
        unset($payload['mileage_at_service']);

        $this->actingAs($this->employee)
            ->post(route('vehicles.service-records.store', $this->vehicle), $payload)
            ->assertRedirect(route('vehicles.show', $this->vehicle));

        $this->assertDatabaseHas('service_records', [
            'vehicle_id'         => $this->vehicle->id,
            'mileage_at_service' => null,
        ]);
        $this->assertSame(10_000, $this->vehicle->fresh()->mileage);
    }

    // -------------------------------------------------------------------------
    // store — validation
    // -------------------------------------------------------------------------

    public function test_store_rejects_missing_service_type_ids(): void
    {
        $payload = $this->validPayload();
        unset($payload['service_type_ids']);

        $this->actingAs($this->employee)
            ->post(route('vehicles.service-records.store', $this->vehicle), $payload)
            ->assertSessionHasErrors('service_type_ids');
    }

    public function test_store_rejects_empty_service_type_ids(): void
    {
        $payload                     = $this->validPayload();
        $payload['service_type_ids'] = [];

        $this->actingAs($this->employee)
            ->post(route('vehicles.service-records.store', $this->vehicle), $payload)
            ->assertSessionHasErrors('service_type_ids');
    }

    public function test_store_rejects_mileage_below_vehicle_mileage(): void
    {
        $payload                      = $this->validPayload();
        $payload['mileage_at_service'] = 9_000;

        $this->actingAs($this->employee)
            ->post(route('vehicles.service-records.store', $this->vehicle), $payload)
            ->assertSessionHasErrors('mileage_at_service');
    }

    public function test_store_rejects_future_serviced_at(): void
    {
        $payload               = $this->validPayload();
        $payload['serviced_at'] = now()->addDay()->toDateString();

        $this->actingAs($this->employee)
            ->post(route('vehicles.service-records.store', $this->vehicle), $payload)
            ->assertSessionHasErrors('serviced_at');
    }

    // -------------------------------------------------------------------------
    // edit / update
    // -------------------------------------------------------------------------

    public function test_employee_can_access_edit_form(): void
    {
        $record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $record->serviceTypes()->attach(ServiceType::first());

        $this->actingAs($this->employee)
            ->get(route('service-records.edit', $record))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('ServiceRecords/Edit'));
    }

    public function test_employee_can_update_service_record(): void
    {
        $record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $record->serviceTypes()->attach(ServiceType::first());

        $newType = ServiceType::where('name', 'Freins')->first();
        $payload = [
            'service_type_ids'   => [$newType->id],
            'mileage_at_service' => 11_000,
            'notes'              => 'Remplacement plaquettes.',
            'serviced_at'        => now()->toDateString(),
        ];

        $this->actingAs($this->employee)
            ->patch(route('service-records.update', $record), $payload)
            ->assertRedirect(route('vehicles.show', $this->vehicle));

        $this->assertDatabaseHas('service_records', [
            'id'                 => $record->id,
            'mileage_at_service' => 11_000,
        ]);
        $this->assertDatabaseHas('service_record_service_type', [
            'service_record_id' => $record->id,
            'service_type_id'   => $newType->id,
        ]);
    }

    public function test_update_rejects_empty_service_type_ids(): void
    {
        $record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $record->serviceTypes()->attach(ServiceType::first());

        $payload = [
            'service_type_ids'   => [],
            'mileage_at_service' => 11_000,
            'serviced_at'        => now()->toDateString(),
        ];

        $this->actingAs($this->employee)
            ->patch(route('service-records.update', $record), $payload)
            ->assertSessionHasErrors('service_type_ids');
    }

    // -------------------------------------------------------------------------
    // destroy — authorization
    // -------------------------------------------------------------------------

    public function test_employee_cannot_delete_service_record(): void
    {
        $record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->actingAs($this->employee)
            ->delete(route('service-records.destroy', $record))
            ->assertForbidden();
    }

    public function test_admin_can_delete_service_record(): void
    {
        $record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->actingAs($this->admin)
            ->delete(route('service-records.destroy', $record))
            ->assertRedirect(route('vehicles.show', $this->vehicle));

        $this->assertSoftDeleted('service_records', ['id' => $record->id]);
    }
}

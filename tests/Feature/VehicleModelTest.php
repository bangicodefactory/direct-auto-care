<?php

namespace Tests\Feature;

use App\Enums\FuelType;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ServiceRecord;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleModelTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // factory + fillable
    // -------------------------------------------------------------------------

    public function test_factory_creates_vehicle_with_required_fields(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->assertNotNull($vehicle->make);
        $this->assertNotNull($vehicle->model);
        $this->assertNotNull($vehicle->year);
        $this->assertNotNull($vehicle->license_plate);
        $this->assertNotNull($vehicle->fuel_type);
    }

    // -------------------------------------------------------------------------
    // casts
    // -------------------------------------------------------------------------

    public function test_year_is_cast_to_integer(): void
    {
        $vehicle = Vehicle::factory()->create(['year' => '2020']);

        $this->assertSame(2020, $vehicle->year);
    }

    public function test_mileage_is_cast_to_integer(): void
    {
        $vehicle = Vehicle::factory()->create(['mileage' => '50000']);

        $this->assertSame(50000, $vehicle->mileage);
    }

    public function test_fuel_type_is_cast_to_enum(): void
    {
        $vehicle = Vehicle::factory()->create(['fuel_type' => 'diesel']);

        $this->assertInstanceOf(FuelType::class, $vehicle->fuel_type);
        $this->assertSame(FuelType::Diesel, $vehicle->fuel_type);
    }

    public function test_all_fuel_type_values_are_valid(): void
    {
        foreach (FuelType::cases() as $type) {
            $vehicle = Vehicle::factory()->create(['fuel_type' => $type->value]);
            $this->assertSame($type, $vehicle->fresh()->fuel_type);
        }
    }

    // -------------------------------------------------------------------------
    // relationships
    // -------------------------------------------------------------------------

    public function test_vehicle_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->assertTrue($vehicle->customer->is($customer));
    }

    public function test_vehicle_has_many_service_records(): void
    {
        $vehicle = Vehicle::factory()->create();
        ServiceRecord::factory(3)->create(['vehicle_id' => $vehicle->id]);

        $this->assertCount(3, $vehicle->serviceRecords);
    }

    public function test_vehicle_has_many_appointments(): void
    {
        $vehicle = Vehicle::factory()->create();
        Appointment::factory(2)->create(['vehicle_id' => $vehicle->id]);

        $this->assertCount(2, $vehicle->appointments);
    }

    // -------------------------------------------------------------------------
    // soft deletes
    // -------------------------------------------------------------------------

    public function test_vehicle_is_soft_deleted(): void
    {
        $vehicle = Vehicle::factory()->create();
        $vehicle->delete();

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
        $this->assertNull(Vehicle::find($vehicle->id));
    }

    public function test_soft_deleted_vehicle_is_excluded_from_default_query(): void
    {
        Vehicle::factory(2)->create();
        $vehicle = Vehicle::factory()->create();
        $vehicle->delete();

        $this->assertCount(2, Vehicle::all());
    }
}

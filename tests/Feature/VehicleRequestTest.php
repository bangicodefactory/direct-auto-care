<?php

namespace Tests\Feature;

use App\Enums\FuelType;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class VehicleRequestTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUpdateRequest(Vehicle $vehicle): UpdateVehicleRequest
    {
        $request = new UpdateVehicleRequest();
        $request->setRouteResolver(function () use ($vehicle) {
            return new class ($vehicle) {
                public function __construct(private Vehicle $vehicle)
                {
                }

                public function parameter(string $key, mixed $default = null): mixed
                {
                    return $key === 'vehicle' ? $this->vehicle : $default;
                }
            };
        });

        return $request;
    }

    private function validStorePayload(Customer $customer): array
    {
        return [
            'customer_id'   => $customer->id,
            'make'          => 'Toyota',
            'model'         => 'Corolla',
            'year'          => 2020,
            'license_plate' => '12345-A-01',
            'fuel_type'     => FuelType::Essence->value,
        ];
    }

    private function validUpdatePayload(Vehicle $vehicle): array
    {
        return [
            'make'          => 'Toyota',
            'model'         => 'Corolla',
            'year'          => 2020,
            'license_plate' => $vehicle->license_plate,
            'mileage'       => $vehicle->mileage,
            'fuel_type'     => FuelType::Essence->value,
        ];
    }

    // -------------------------------------------------------------------------
    // StoreVehicleRequest — valid payload
    // -------------------------------------------------------------------------

    public function test_store_valid_payload_passes(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();

        $v = Validator::make($this->validStorePayload($customer), $rules);

        $this->assertFalse($v->fails());
    }

    // -------------------------------------------------------------------------
    // StoreVehicleRequest — required fields
    // -------------------------------------------------------------------------

    public function test_store_make_is_required(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();
        $payload  = $this->validStorePayload($customer);
        unset($payload['make']);

        $v = Validator::make($payload, $rules);

        $this->assertArrayHasKey('make', $v->errors()->toArray());
    }

    public function test_store_model_is_required(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();
        $payload  = $this->validStorePayload($customer);
        unset($payload['model']);

        $v = Validator::make($payload, $rules);

        $this->assertArrayHasKey('model', $v->errors()->toArray());
    }

    public function test_store_year_is_required(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();
        $payload  = $this->validStorePayload($customer);
        unset($payload['year']);

        $v = Validator::make($payload, $rules);

        $this->assertArrayHasKey('year', $v->errors()->toArray());
    }

    public function test_store_license_plate_is_required(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();
        $payload  = $this->validStorePayload($customer);
        unset($payload['license_plate']);

        $v = Validator::make($payload, $rules);

        $this->assertArrayHasKey('license_plate', $v->errors()->toArray());
    }

    public function test_store_fuel_type_is_required(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();
        $payload  = $this->validStorePayload($customer);
        unset($payload['fuel_type']);

        $v = Validator::make($payload, $rules);

        $this->assertArrayHasKey('fuel_type', $v->errors()->toArray());
    }

    // -------------------------------------------------------------------------
    // StoreVehicleRequest — year range
    // -------------------------------------------------------------------------

    public function test_store_year_rejects_before_1950(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();
        $payload  = array_merge($this->validStorePayload($customer), ['year' => 1949]);

        $v = Validator::make($payload, $rules);

        $this->assertArrayHasKey('year', $v->errors()->toArray());
    }

    public function test_store_year_rejects_too_far_future(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();
        $payload  = array_merge($this->validStorePayload($customer), ['year' => date('Y') + 2]);

        $v = Validator::make($payload, $rules);

        $this->assertArrayHasKey('year', $v->errors()->toArray());
    }

    public function test_store_year_accepts_boundary_values(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();

        foreach ([1950, date('Y') + 1] as $year) {
            $v = Validator::make(
                array_merge($this->validStorePayload($customer), ['year' => $year, 'license_plate' => "0000{$year}-A-01"]),
                $rules
            );
            $this->assertArrayNotHasKey('year', $v->errors()->toArray(), "Year $year should be valid");
        }
    }

    // -------------------------------------------------------------------------
    // StoreVehicleRequest — license_plate uniqueness
    // -------------------------------------------------------------------------

    public function test_store_license_plate_unique_is_enforced(): void
    {
        $customer = Customer::factory()->create();
        Vehicle::factory()->create(['license_plate' => '12345-A-01', 'customer_id' => $customer->id]);

        $rules = (new StoreVehicleRequest())->rules();
        $v     = Validator::make($this->validStorePayload($customer), $rules);

        $this->assertArrayHasKey('license_plate', $v->errors()->toArray());
    }

    // -------------------------------------------------------------------------
    // StoreVehicleRequest — VIN
    // -------------------------------------------------------------------------

    public function test_store_vin_is_optional(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();
        $v        = Validator::make($this->validStorePayload($customer), $rules);

        $this->assertFalse($v->fails());
    }

    public function test_store_vin_must_be_exactly_17_characters(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();

        foreach (['1HGCM82633A00000', '1HGCM82633A000000X'] as $invalid) {
            $v = Validator::make(
                array_merge($this->validStorePayload($customer), ['vin' => $invalid]),
                $rules
            );
            $this->assertArrayHasKey('vin', $v->errors()->toArray(), "VIN '$invalid' should fail size:17");
        }
    }

    public function test_store_vin_unique_is_enforced(): void
    {
        $customer = Customer::factory()->create();
        Vehicle::factory()->create(['vin' => '1HGCM82633A004352', 'customer_id' => $customer->id]);

        $rules   = (new StoreVehicleRequest())->rules();
        $payload = array_merge($this->validStorePayload($customer), ['license_plate' => '99999-B-02', 'vin' => '1HGCM82633A004352']);
        $v       = Validator::make($payload, $rules);

        $this->assertArrayHasKey('vin', $v->errors()->toArray());
    }

    // -------------------------------------------------------------------------
    // StoreVehicleRequest — fuel_type
    // -------------------------------------------------------------------------

    public function test_store_fuel_type_rejects_invalid_values(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();

        foreach (['gasoline', 'petrol', '', 'ESSENCE'] as $invalid) {
            $v = Validator::make(
                array_merge($this->validStorePayload($customer), ['fuel_type' => $invalid]),
                $rules
            );
            $this->assertArrayHasKey('fuel_type', $v->errors()->toArray(), "fuel_type '$invalid' should fail");
        }
    }

    public function test_store_fuel_type_accepts_all_enum_values(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();

        foreach (FuelType::cases() as $i => $type) {
            $v = Validator::make(
                array_merge($this->validStorePayload($customer), ['license_plate' => "0000{$i}-A-01", 'fuel_type' => $type->value]),
                $rules
            );
            $this->assertArrayNotHasKey('fuel_type', $v->errors()->toArray(), "fuel_type '{$type->value}' should be valid");
        }
    }

    // -------------------------------------------------------------------------
    // StoreVehicleRequest — mileage
    // -------------------------------------------------------------------------

    public function test_store_mileage_is_optional(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();
        $payload  = $this->validStorePayload($customer);
        unset($payload['mileage']);

        $v = Validator::make($payload, $rules);

        $this->assertFalse($v->fails());
    }

    public function test_store_mileage_must_be_non_negative(): void
    {
        $customer = Customer::factory()->create();
        $rules    = (new StoreVehicleRequest())->rules();
        $payload  = array_merge($this->validStorePayload($customer), ['mileage' => -1]);

        $v = Validator::make($payload, $rules);

        $this->assertArrayHasKey('mileage', $v->errors()->toArray());
    }

    // -------------------------------------------------------------------------
    // StoreVehicleRequest — customer_id
    // -------------------------------------------------------------------------

    public function test_store_customer_id_must_exist(): void
    {
        $rules   = (new StoreVehicleRequest())->rules();
        $payload = [
            'customer_id'   => 99999,
            'make'          => 'Toyota',
            'model'         => 'Corolla',
            'year'          => 2020,
            'license_plate' => '12345-A-01',
            'fuel_type'     => FuelType::Essence->value,
        ];

        $v = Validator::make($payload, $rules);

        $this->assertArrayHasKey('customer_id', $v->errors()->toArray());
    }

    // -------------------------------------------------------------------------
    // UpdateVehicleRequest — valid payload
    // -------------------------------------------------------------------------

    public function test_update_valid_payload_passes(): void
    {
        $vehicle = Vehicle::factory()->create(['mileage' => 10_000]);
        $rules   = $this->makeUpdateRequest($vehicle)->rules();

        $v = Validator::make($this->validUpdatePayload($vehicle), $rules);

        $this->assertFalse($v->fails());
    }

    // -------------------------------------------------------------------------
    // UpdateVehicleRequest — license_plate uniqueness
    // -------------------------------------------------------------------------

    public function test_update_license_plate_allows_own(): void
    {
        $vehicle = Vehicle::factory()->create(['license_plate' => '12345-A-01', 'mileage' => 0]);
        $rules   = $this->makeUpdateRequest($vehicle)->rules();

        $v = Validator::make($this->validUpdatePayload($vehicle), $rules);

        $this->assertArrayNotHasKey('license_plate', $v->errors()->toArray());
    }

    public function test_update_license_plate_rejects_another_vehicles_plate(): void
    {
        Vehicle::factory()->create(['license_plate' => '11111-A-01']);
        $vehicle = Vehicle::factory()->create(['license_plate' => '22222-B-02', 'mileage' => 0]);
        $rules   = $this->makeUpdateRequest($vehicle)->rules();

        $payload = array_merge($this->validUpdatePayload($vehicle), ['license_plate' => '11111-A-01']);
        $v       = Validator::make($payload, $rules);

        $this->assertArrayHasKey('license_plate', $v->errors()->toArray());
    }

    // -------------------------------------------------------------------------
    // UpdateVehicleRequest — VIN uniqueness
    // -------------------------------------------------------------------------

    public function test_update_vin_allows_own(): void
    {
        $vehicle = Vehicle::factory()->create(['vin' => '1HGCM82633A004352', 'mileage' => 0]);
        $rules   = $this->makeUpdateRequest($vehicle)->rules();

        $payload = array_merge($this->validUpdatePayload($vehicle), ['vin' => '1HGCM82633A004352']);
        $v       = Validator::make($payload, $rules);

        $this->assertArrayNotHasKey('vin', $v->errors()->toArray());
    }

    public function test_update_vin_rejects_another_vehicles_vin(): void
    {
        Vehicle::factory()->create(['vin' => '1HGCM82633A004352']);
        $vehicle = Vehicle::factory()->create(['vin' => '2T1BURHE0JC043821', 'mileage' => 0]);
        $rules   = $this->makeUpdateRequest($vehicle)->rules();

        $payload = array_merge($this->validUpdatePayload($vehicle), ['vin' => '1HGCM82633A004352']);
        $v       = Validator::make($payload, $rules);

        $this->assertArrayHasKey('vin', $v->errors()->toArray());
    }

    // -------------------------------------------------------------------------
    // UpdateVehicleRequest — mileage (MileageNotDecreasing rule)
    // -------------------------------------------------------------------------

    public function test_update_mileage_can_stay_the_same(): void
    {
        $vehicle = Vehicle::factory()->create(['mileage' => 50_000]);
        $rules   = $this->makeUpdateRequest($vehicle)->rules();

        $payload = array_merge($this->validUpdatePayload($vehicle), ['mileage' => 50_000]);
        $v       = Validator::make($payload, $rules);

        $this->assertArrayNotHasKey('mileage', $v->errors()->toArray());
    }

    public function test_update_mileage_can_increase(): void
    {
        $vehicle = Vehicle::factory()->create(['mileage' => 50_000]);
        $rules   = $this->makeUpdateRequest($vehicle)->rules();

        $payload = array_merge($this->validUpdatePayload($vehicle), ['mileage' => 60_000]);
        $v       = Validator::make($payload, $rules);

        $this->assertArrayNotHasKey('mileage', $v->errors()->toArray());
    }

    public function test_update_mileage_cannot_decrease(): void
    {
        $vehicle = Vehicle::factory()->create(['mileage' => 50_000]);
        $rules   = $this->makeUpdateRequest($vehicle)->rules();

        $payload = array_merge($this->validUpdatePayload($vehicle), ['mileage' => 49_999]);
        $v       = Validator::make($payload, $rules);

        $this->assertArrayHasKey('mileage', $v->errors()->toArray());
    }

    public function test_update_mileage_cannot_be_negative(): void
    {
        $vehicle = Vehicle::factory()->create(['mileage' => 0]);
        $rules   = $this->makeUpdateRequest($vehicle)->rules();

        $payload = array_merge($this->validUpdatePayload($vehicle), ['mileage' => -1]);
        $v       = Validator::make($payload, $rules);

        $this->assertArrayHasKey('mileage', $v->errors()->toArray());
    }
}

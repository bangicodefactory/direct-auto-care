<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\ServiceOrderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceOrderStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
    }

    private function employee(): User
    {
        return User::factory()->create()->assignRole('employee');
    }

    /**
     * A complete, valid reception payload (photos + signature are required).
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Customer $customer, Vehicle $vehicle, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
            'fuel_level'  => 50,
            'photos'      => [UploadedFile::fake()->image('photo.jpg')],
            'signature'   => UploadedFile::fake()->image('signature.png'),
        ], $overrides);
    }

    public function test_walk_in_creates_an_order_in_reception_and_redirects(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($this->employee())
            ->post(route('workshop.store'), $this->validPayload($customer, $vehicle, ['mileage_in' => 84500]));

        $order = ServiceOrder::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('workshop.show', $order));

        $this->assertSame(ServiceOrderStatus::Reception, $order->status);
        $this->assertStringStartsWith('OS-', $order->number);
        $this->assertSame(84500, $order->mileage_in);
        $this->assertNotNull($order->received_at);
    }

    public function test_linked_appointment_is_marked_completed(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
            'status'      => AppointmentStatus::Confirmed->value,
        ]);

        $this->actingAs($this->employee())
            ->post(route('workshop.store'), $this->validPayload($customer, $vehicle, ['appointment_id' => $appointment->id]))
            ->assertRedirect();

        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
    }

    public function test_a_non_confirmed_linked_appointment_is_not_completed(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
            'status'      => AppointmentStatus::Cancelled->value,
        ]);

        $this->actingAs($this->employee())
            ->post(route('workshop.store'), $this->validPayload($customer, $vehicle, ['appointment_id' => $appointment->id]))
            ->assertRedirect();

        // The cancelled appointment must stay cancelled.
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
    }

    public function test_vehicle_must_belong_to_the_customer(): void
    {
        $customer = Customer::factory()->create();
        $other    = Customer::factory()->create();
        $foreignVehicle = Vehicle::factory()->create(['customer_id' => $other->id]);

        $this->actingAs($this->employee())
            ->post(route('workshop.store'), $this->validPayload($customer, $foreignVehicle))
            ->assertSessionHasErrors('vehicle_id');

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_reception_condition_is_persisted_as_a_map(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->employee())
            ->post(route('workshop.store'), $this->validPayload($customer, $vehicle, [
                'reception_condition' => [
                    'FRONT_BONNET'          => '#FF1A1A',
                    'LEFT_SIDE_DRIVER_DOOR' => '#E65100',
                ],
            ]))
            ->assertRedirect();

        $order = ServiceOrder::first();
        $this->assertSame('#FF1A1A', $order->reception_condition['FRONT_BONNET']);
        $this->assertSame('#E65100', $order->reception_condition['LEFT_SIDE_DRIVER_DOOR']);
    }

    public function test_invalid_condition_key_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->employee())
            ->post(route('workshop.store'), $this->validPayload($customer, $vehicle, [
                'reception_condition' => ['not a valid part!' => '#FFFFFF'],
            ]))
            ->assertSessionHasErrors('reception_condition');

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_invalid_condition_colour_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->employee())
            ->post(route('workshop.store'), $this->validPayload($customer, $vehicle, [
                'reception_condition' => ['FRONT_BONNET' => 'red'],
            ]))
            ->assertSessionHasErrors('reception_condition.FRONT_BONNET');
    }

    // -------------------------------------------------------------------------
    // Photos, fuel level, signature
    // -------------------------------------------------------------------------

    public function test_photos_signature_and_fuel_are_stored(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->employee())
            ->post(route('workshop.store'), $this->validPayload($customer, $vehicle, [
                'fuel_level' => 70,
                'photos'     => [
                    UploadedFile::fake()->image('a.jpg'),
                    UploadedFile::fake()->image('b.jpg'),
                ],
            ]))
            ->assertRedirect();

        $order = ServiceOrder::first();
        $this->assertSame(70, $order->fuel_level);
        $this->assertCount(2, $order->reception_photos);
        Storage::disk('public')->assertExists($order->reception_photos[0]);
        Storage::disk('public')->assertExists($order->reception_photos[1]);
        $this->assertNotNull($order->signature_path);
        Storage::disk('public')->assertExists($order->signature_path);
    }

    public function test_at_least_one_photo_is_required(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->employee())
            ->post(route('workshop.store'), $this->validPayload($customer, $vehicle, ['photos' => []]))
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_more_than_three_photos_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->employee())
            ->post(route('workshop.store'), $this->validPayload($customer, $vehicle, [
                'photos' => [
                    UploadedFile::fake()->image('1.jpg'),
                    UploadedFile::fake()->image('2.jpg'),
                    UploadedFile::fake()->image('3.jpg'),
                    UploadedFile::fake()->image('4.jpg'),
                ],
            ]))
            ->assertSessionHasErrors('photos');
    }

    public function test_non_image_photo_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->employee())
            ->post(route('workshop.store'), $this->validPayload($customer, $vehicle, [
                'photos' => [UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf')],
            ]))
            ->assertSessionHasErrors('photos.0');
    }

    public function test_signature_is_required(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $payload = $this->validPayload($customer, $vehicle);
        unset($payload['signature']);

        $this->actingAs($this->employee())
            ->post(route('workshop.store'), $payload)
            ->assertSessionHasErrors('signature');

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_fuel_level_out_of_range_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->employee())
            ->post(route('workshop.store'), $this->validPayload($customer, $vehicle, ['fuel_level' => 150]))
            ->assertSessionHasErrors('fuel_level');
    }

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_guest_cannot_access_reception_form(): void
    {
        $this->get(route('workshop.create'))->assertRedirect(route('login'));
    }

    public function test_user_without_role_cannot_create(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs(User::factory()->create())
            ->post(route('workshop.store'), $this->validPayload($customer, $vehicle))
            ->assertForbidden();
    }
}

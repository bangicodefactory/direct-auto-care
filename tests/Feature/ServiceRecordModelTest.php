<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ServiceRecord;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ServiceRecordModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // -------------------------------------------------------------------------
    // factory + fillable
    // -------------------------------------------------------------------------

    public function test_factory_creates_service_record_with_required_fields(): void
    {
        $record = ServiceRecord::factory()->create();

        $this->assertNotNull($record->vehicle_id);
        $this->assertNotNull($record->serviced_at);
    }

    // -------------------------------------------------------------------------
    // casts
    // -------------------------------------------------------------------------

    public function test_serviced_at_is_cast_to_carbon_date(): void
    {
        $record = ServiceRecord::factory()->create(['serviced_at' => '2025-06-15']);

        $this->assertInstanceOf(Carbon::class, $record->serviced_at);
        $this->assertSame('2025-06-15', $record->serviced_at->toDateString());
    }

    public function test_mileage_at_service_is_cast_to_integer(): void
    {
        $record = ServiceRecord::factory()->create(['mileage_at_service' => '42000']);

        $this->assertSame(42000, $record->mileage_at_service);
    }

    public function test_mileage_at_service_can_be_null(): void
    {
        $record = ServiceRecord::factory()->create(['mileage_at_service' => null]);

        $this->assertNull($record->mileage_at_service);
    }

    // -------------------------------------------------------------------------
    // relationships
    // -------------------------------------------------------------------------

    public function test_service_record_belongs_to_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();
        $record  = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->assertTrue($record->vehicle->is($vehicle));
    }

    public function test_service_record_invoice_is_null_by_default(): void
    {
        $record = ServiceRecord::factory()->create(['invoice_id' => null]);

        $this->assertNull($record->invoice);
    }

    public function test_service_record_belongs_to_invoice_when_set(): void
    {
        $invoice = Invoice::create(['customer_id' => Customer::factory()->create()->id]);
        $record  = ServiceRecord::factory()->create(['invoice_id' => $invoice->id]);

        $this->assertTrue($record->invoice->is($invoice));
    }

    public function test_invoice_has_many_service_records(): void
    {
        $invoice = Invoice::create(['customer_id' => Customer::factory()->create()->id]);
        ServiceRecord::factory(2)->create(['invoice_id' => $invoice->id]);

        $this->assertCount(2, $invoice->serviceRecords);
    }

    public function test_service_record_belongs_to_many_service_types(): void
    {
        $this->seed(ServiceTypeSeeder::class);

        $record = ServiceRecord::factory()->create();
        $types  = ServiceType::whereIn('name', ['Vidange', 'Freins'])->get();
        $record->serviceTypes()->attach($types);

        $this->assertCount(2, $record->fresh()->serviceTypes);
    }

    // -------------------------------------------------------------------------
    // soft deletes
    // -------------------------------------------------------------------------

    public function test_service_record_is_soft_deleted(): void
    {
        $record = ServiceRecord::factory()->create();
        $record->delete();

        $this->assertSoftDeleted('service_records', ['id' => $record->id]);
        $this->assertNull(ServiceRecord::find($record->id));
    }

    public function test_soft_deleted_service_record_is_excluded_from_default_query(): void
    {
        ServiceRecord::factory(2)->create();
        $record = ServiceRecord::factory()->create();
        $record->delete();

        $this->assertCount(2, ServiceRecord::all());
    }

    // -------------------------------------------------------------------------
    // invoice nullOnDelete edge case
    // -------------------------------------------------------------------------

    public function test_invoice_id_remains_set_after_invoice_soft_delete(): void
    {
        $invoice = Invoice::create(['customer_id' => Customer::factory()->create()->id]);
        $record  = ServiceRecord::factory()->create(['invoice_id' => $invoice->id]);

        $invoice->delete();

        // Soft delete does NOT trigger nullOnDelete — the FK value is preserved.
        $this->assertSame($invoice->id, $record->fresh()->invoice_id);
        // But the ORM returns null because soft-deleted models are excluded.
        $this->assertNull($record->fresh()->invoice);
    }

    public function test_invoice_id_is_nulled_after_invoice_force_delete(): void
    {
        $invoice = Invoice::create(['customer_id' => Customer::factory()->create()->id]);
        $record  = ServiceRecord::factory()->create(['invoice_id' => $invoice->id]);

        $invoice->forceDelete();

        $this->assertNull($record->fresh()->invoice_id);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ServiceRecord;
use App\Models\ServiceType;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTypeSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // -------------------------------------------------------------------------
    // ServiceTypeSeeder
    // -------------------------------------------------------------------------

    public function test_seeder_creates_all_seven_types(): void
    {
        $this->seed(ServiceTypeSeeder::class);

        $this->assertDatabaseCount('service_types', 7);

        foreach (['Vidange', 'Filtres', 'Freins', 'Batterie', 'Pneus', 'Climatisation', 'Réparations'] as $name) {
            $this->assertDatabaseHas('service_types', ['name' => $name]);
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ServiceTypeSeeder::class);
        $this->seed(ServiceTypeSeeder::class);

        $this->assertDatabaseCount('service_types', 7);
    }

    // -------------------------------------------------------------------------
    // pivot — attach / detach
    // -------------------------------------------------------------------------

    public function test_service_record_can_have_multiple_service_types(): void
    {
        $this->seed(ServiceTypeSeeder::class);

        $record  = ServiceRecord::factory()->create();
        $types   = ServiceType::whereIn('name', ['Vidange', 'Filtres'])->get();

        $record->serviceTypes()->attach($types);

        $this->assertCount(2, $record->serviceTypes);
        $this->assertDatabaseHas('service_record_service_type', [
            'service_record_id' => $record->id,
            'service_type_id'   => $types->firstWhere('name', 'Vidange')->id,
        ]);
    }

    public function test_pivot_rows_are_deleted_when_service_record_is_deleted(): void
    {
        $this->seed(ServiceTypeSeeder::class);

        $record = ServiceRecord::factory()->create();
        $type   = ServiceType::where('name', 'Freins')->first();
        $record->serviceTypes()->attach($type);

        $record->forceDelete();

        $this->assertDatabaseMissing('service_record_service_type', [
            'service_record_id' => $record->id,
        ]);
    }

    public function test_one_service_type_can_appear_on_multiple_service_records(): void
    {
        $this->seed(ServiceTypeSeeder::class);

        $type    = ServiceType::where('name', 'Pneus')->first();
        $record1 = ServiceRecord::factory()->create();
        $record2 = ServiceRecord::factory()->create();

        $record1->serviceTypes()->attach($type);
        $record2->serviceTypes()->attach($type);

        $this->assertCount(2, $type->serviceRecords);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Import\VehicleImportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class VehicleImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // =========================================================================
    // Authorization
    // =========================================================================

    public function test_guest_is_redirected_from_import(): void
    {
        $this->post(route('vehicles.import'))->assertRedirect(route('login'));
    }

    public function test_roleless_user_cannot_import(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('vehicles.import'), ['file' => $this->xlsx([])])
            ->assertForbidden();
    }

    public function test_employee_can_import(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $customer = Customer::factory()->create();

        $this->actingAs($employee)
            ->from(route('vehicles.index'))
            ->post(route('vehicles.import'), ['file' => $this->xlsx([$this->row(customerId: $customer->id)])])
            ->assertRedirect(route('vehicles.index'))
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 1);

        $this->assertDatabaseHas('vehicles', ['customer_id' => $customer->id, 'license_plate' => '12345-A-6']);
    }

    public function test_employee_can_download_template(): void
    {
        $employee = User::factory()->create()->assignRole('employee');

        $response = $this->actingAs($employee)->get(route('vehicles.import.template'));

        $response->assertOk();
        $this->assertStringContainsString('modele_vehicules.xlsx', $response->headers->get('Content-Disposition') ?? '');
    }

    // =========================================================================
    // Customer resolution
    // =========================================================================

    public function test_resolves_customer_by_phone(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create(['phone' => '+212611223344']);

        $this->actingAs($admin)
            ->from(route('vehicles.index'))
            ->post(route('vehicles.import'), ['file' => $this->xlsx([$this->row(phone: '+212611223344')])])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 1 && $r['failed'] === []);

        $this->assertDatabaseHas('vehicles', ['customer_id' => $customer->id, 'license_plate' => '12345-A-6']);
    }

    // Note: `customers.phone` is unique, so a phone can never match >1 customer.
    // The service still guards the ambiguous case defensively (see resolveCustomer),
    // but it is unreachable through normal data and therefore not exercised here.

    public function test_unknown_phone_is_reported(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        Customer::factory()->create(['phone' => '+212600000000']);

        $this->actingAs($admin)
            ->from(route('vehicles.index'))
            ->post(route('vehicles.import'), ['file' => $this->xlsx([$this->row(phone: '+212611111111')])])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 0 && count($r['failed']) === 1);

        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_unknown_customer_id_is_reported(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->from(route('vehicles.index'))
            ->post(route('vehicles.import'), ['file' => $this->xlsx([$this->row(customerId: 99999)])])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 0 && count($r['failed']) === 1);

        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_missing_customer_reference_is_reported(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->from(route('vehicles.index'))
            ->post(route('vehicles.import'), ['file' => $this->xlsx([$this->row()])])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 0 && count($r['failed']) === 1);
    }

    // =========================================================================
    // Field validation + duplicates
    // =========================================================================

    public function test_bad_fuel_type_is_reported_and_rest_imported(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();

        $file = $this->xlsx([
            $this->row(customerId: $customer->id, plate: 'BAD-1', fuel: 'kerosene'),
            $this->row(customerId: $customer->id, plate: 'OK-1'),
        ]);

        $this->actingAs($admin)
            ->from(route('vehicles.index'))
            ->post(route('vehicles.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 1
                && count($r['failed']) === 1
                && $r['failed'][0]['row'] === 2);

        $this->assertDatabaseMissing('vehicles', ['license_plate' => 'BAD-1']);
        $this->assertDatabaseHas('vehicles', ['license_plate' => 'OK-1']);
    }

    public function test_bad_year_is_reported(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();

        $file = $this->xlsx([$this->row(customerId: $customer->id, year: 1800)]);

        $this->actingAs($admin)
            ->from(route('vehicles.index'))
            ->post(route('vehicles.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 0 && count($r['failed']) === 1);
    }

    public function test_vin_wrong_length_is_reported(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();

        $file = $this->xlsx([$this->row(customerId: $customer->id, vin: 'TOOSHORT')]);

        $this->actingAs($admin)
            ->from(route('vehicles.index'))
            ->post(route('vehicles.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 0 && count($r['failed']) === 1);
    }

    public function test_intra_file_duplicate_plate_is_reported(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();

        $file = $this->xlsx([
            $this->row(customerId: $customer->id, plate: 'SAME-1'),
            $this->row(customerId: $customer->id, plate: 'SAME-1'),
        ]);

        $this->actingAs($admin)
            ->from(route('vehicles.index'))
            ->post(route('vehicles.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 1 && count($r['failed']) === 1);

        $this->assertSame(1, Vehicle::where('license_plate', 'SAME-1')->count());
    }

    public function test_duplicate_of_existing_plate_is_reported(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();
        Vehicle::factory()->create(['license_plate' => 'EXIST-1']);

        $file = $this->xlsx([$this->row(customerId: $customer->id, plate: 'EXIST-1')]);

        $this->actingAs($admin)
            ->from(route('vehicles.index'))
            ->post(route('vehicles.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 0 && count($r['failed']) === 1);

        $this->assertSame(1, Vehicle::where('license_plate', 'EXIST-1')->count());
    }

    public function test_duplicate_of_a_soft_deleted_plate_is_reported_not_fatal(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();
        Vehicle::factory()->create(['license_plate' => 'GONE-1'])->delete();

        // The DB unique index on `license_plate` is global; reusing a trashed plate
        // must be a reported row error, not a UniqueConstraintViolation on insert.
        $file = $this->xlsx([$this->row(customerId: $customer->id, plate: 'GONE-1')]);

        $this->actingAs($admin)
            ->from(route('vehicles.index'))
            ->post(route('vehicles.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 0 && count($r['failed']) === 1);
    }

    public function test_non_xlsx_upload_is_rejected(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->from(route('vehicles.index'))
            ->post(route('vehicles.import'), ['file' => UploadedFile::fake()->create('data.csv', 10, 'text/csv')])
            ->assertSessionHasErrors('file');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build one vehicle row aligned to VehicleImportService::HEADERS:
     * customer_id, customer_phone, make, model, year, license_plate, vin, mileage, fuel_type, color.
     *
     * @return array<int, string|int|null>
     */
    private function row(
        int|string $customerId = '',
        string $phone = '',
        string $plate = '12345-A-6',
        int $year = 2020,
        string $fuel = 'essence',
        string $vin = '',
    ): array {
        return [$customerId, $phone, 'Dacia', 'Logan', $year, $plate, $vin, 100000, $fuel, 'Gris'];
    }

    /**
     * @param  list<array<int, string|int|null>>  $rows
     */
    private function xlsx(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'test_vehicles_');

        $writer = new Writer();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(VehicleImportService::HEADERS));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return new UploadedFile(
            $path,
            'vehicules.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}

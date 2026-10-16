<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\Import\ProductImportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ProductImportTest extends TestCase
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
        $this->post(route('products.import'))->assertRedirect(route('login'));
    }

    public function test_employee_cannot_import_products(): void
    {
        $employee = User::factory()->create()->assignRole('employee');

        $this->actingAs($employee)
            ->post(route('products.import'), ['file' => $this->xlsx([$this->validRow()])])
            ->assertForbidden();
    }

    public function test_admin_can_download_template(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('products.import.template'));

        $response->assertOk();
        $this->assertStringContainsString('modele_produits.xlsx', $response->headers->get('Content-Disposition') ?? '');
    }

    // =========================================================================
    // Import behaviour
    // =========================================================================

    public function test_admin_imports_valid_rows(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $file = $this->xlsx([
            ['huile', 'Huile 5W30', 'SKU-A', 'L', 100, 180, 20, 5],
            ['filtre', 'Filtre à huile', 'SKU-B', 'unité', 15, 40, 50, 10],
        ]);

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $file])
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 2 && $r['failed'] === []);

        // The rows deliberately use the legacy spellings 'L' and 'unité':
        // the import must accept them and store the canonical vocabulary.
        $this->assertDatabaseHas('products', ['sku' => 'SKU-A', 'quantity_on_hand' => 20, 'unit' => 'litre']);
        $this->assertDatabaseHas('products', ['sku' => 'SKU-B', 'name' => 'Filtre à huile', 'unit' => 'unite']);
    }

    public function test_unit_synonyms_are_normalized_on_import(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $file = $this->xlsx([
            ['piece', 'Plaquettes avant', 'SKU-P', 'pièce', 50, 90, 10, 2],
            ['piece', 'Disques avant', 'SKU-D', 'PAIRES', 200, 350, 6, 2],
        ]);

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 2);

        $this->assertDatabaseHas('products', ['sku' => 'SKU-P', 'unit' => 'unite']);
        $this->assertDatabaseHas('products', ['sku' => 'SKU-D', 'unit' => 'paire']);
    }

    public function test_unknown_unit_defaults_by_category_when_units_are_hidden(): void
    {
        config(['company.show_product_units' => false]);
        $admin = User::factory()->create()->assignRole('admin');

        $file = $this->xlsx([
            ['huile', 'Huile en gallons', 'SKU-GAL', 'gallon', 10, 20, 5, 2],
            ['piece', 'Pièce en tonneau', 'SKU-TON-H', 'tonneau', 10, 20, 5, 2],
        ]);

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 2 && $r['failed'] === []);

        $this->assertDatabaseHas('products', ['sku' => 'SKU-GAL', 'unit' => 'litre']);
        $this->assertDatabaseHas('products', ['sku' => 'SKU-TON-H', 'unit' => 'unite']);
    }

    public function test_unknown_unit_row_is_reported_and_skipped(): void
    {
        config(['company.show_product_units' => true]);
        $admin = User::factory()->create()->assignRole('admin');

        $file = $this->xlsx([
            ['piece', 'Unité inconnue', 'SKU-TON', 'tonneau', 10, 20, 5, 2],
            ['huile', 'Bonne ligne', 'SKU-OK2', 'L', 10, 20, 5, 2],
        ]);

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 1
                && count($r['failed']) === 1
                && $r['failed'][0]['row'] === 2);

        $this->assertDatabaseMissing('products', ['sku' => 'SKU-TON']);
        $this->assertDatabaseHas('products', ['sku' => 'SKU-OK2', 'unit' => 'litre']);
    }

    public function test_blank_unit_is_rejected_when_units_are_shown(): void
    {
        config(['company.show_product_units' => true]);
        $admin = User::factory()->create()->assignRole('admin');

        $file = $this->xlsx([
            ['huile', 'Sans unité', 'SKU-NOU', '', 10, 20, 5, 2],
        ]);

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 0 && count($r['failed']) === 1);

        $this->assertDatabaseMissing('products', ['sku' => 'SKU-NOU']);
    }

    public function test_blank_unit_defaults_by_category_when_units_are_hidden(): void
    {
        config(['company.show_product_units' => false]);
        $admin = User::factory()->create()->assignRole('admin');

        $file = $this->xlsx([
            ['huile', 'Huile sans unité', 'SKU-NOU-H', '', 10, 20, 5, 2],
            ['piece', 'Pièce sans unité', 'SKU-NOU-P', null, 10, 20, 5, 2],
            ['piece', 'Pièce avec unité', 'SKU-AVEC', 'paire', 10, 20, 5, 2],
        ]);

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 3 && $r['failed'] === []);

        $this->assertDatabaseHas('products', ['sku' => 'SKU-NOU-H', 'unit' => 'litre']);
        $this->assertDatabaseHas('products', ['sku' => 'SKU-NOU-P', 'unit' => 'unite']);
        // An explicit unit is never overridden by the default.
        $this->assertDatabaseHas('products', ['sku' => 'SKU-AVEC', 'unit' => 'paire']);
    }

    public function test_import_writes_quantity_without_stock_movement(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $this->xlsx([$this->validRow()])])
            ->assertRedirect();

        $this->assertSame(1, Product::count());
        // Opening stock is written directly, matching ProductsController::store().
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_invalid_category_row_is_reported_and_skipped(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $file = $this->xlsx([
            ['nope', 'Mauvaise catégorie', 'SKU-BAD', 'L', 10, 20, 5, 2],
            ['huile', 'Bonne ligne', 'SKU-OK', 'L', 10, 20, 5, 2],
        ]);

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 1
                && count($r['failed']) === 1
                && $r['failed'][0]['row'] === 2);

        $this->assertDatabaseMissing('products', ['sku' => 'SKU-BAD']);
        $this->assertDatabaseHas('products', ['sku' => 'SKU-OK']);
    }

    public function test_invalid_category_error_message_is_in_french(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        // The `enum` rule must resolve to the French translation (lang/fr/validation.php),
        // not Laravel's English default — the bulk import is the first place a free-text
        // enum value can be submitted, so it surfaces any missing translation.
        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $this->xlsx([
                ['categorie_invalide', 'Ligne KO', 'SKU-FR', 'L', 10, 20, 5, 2],
            ])])
            ->assertSessionHas('importReport', fn ($r) => $r['failed'][0]['errors'][0]
                === 'La valeur sélectionnée pour catégorie est invalide.');
    }

    public function test_missing_price_sell_is_reported(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $file = $this->xlsx([
            ['huile', 'Sans prix', 'SKU-NP', 'L', 10, '', 5, 2],
        ]);

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 0 && count($r['failed']) === 1);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_intra_file_duplicate_sku_is_reported(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $file = $this->xlsx([
            ['huile', 'Première', 'DUP', 'L', 10, 20, 5, 2],
            ['huile', 'Doublon', 'DUP', 'L', 10, 20, 5, 2],
        ]);

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 1 && count($r['failed']) === 1);

        $this->assertSame(1, Product::where('sku', 'DUP')->count());
    }

    public function test_duplicate_of_existing_sku_is_reported(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        Product::factory()->create(['sku' => 'EXISTS']);

        $file = $this->xlsx([
            ['huile', 'Conflit', 'EXISTS', 'L', 10, 20, 5, 2],
        ]);

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $file])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 0 && count($r['failed']) === 1);

        $this->assertSame(1, Product::where('sku', 'EXISTS')->count());
    }

    public function test_duplicate_of_a_soft_deleted_sku_is_reported_not_fatal(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        Product::factory()->create(['sku' => 'GONE'])->delete();

        // The DB unique index on `sku` is global; reusing a trashed SKU must be a
        // reported row error, not a UniqueConstraintViolation that aborts the batch.
        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $this->xlsx([
                ['huile', 'Réédition', 'GONE', 'L', 10, 20, 5, 2],
            ])])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 0 && count($r['failed']) === 1);
    }

    public function test_rows_beyond_the_cap_are_truncated(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        // MAX_ROWS is 2000; feed one more valid (blank-sku) row than the cap.
        $rows = array_fill(0, 2001, ['huile', 'Ligne', '', 'L', 10, 20, 5, 2]);

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => $this->xlsx($rows)])
            ->assertSessionHas('importReport', fn ($r) => $r['imported'] === 2000
                && $r['truncated'] === true
                && $r['failed'] === []);

        $this->assertSame(2000, Product::count());
    }

    public function test_non_xlsx_upload_is_rejected(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->from(route('products.index'))
            ->post(route('products.import'), ['file' => UploadedFile::fake()->create('data.csv', 10, 'text/csv')])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('products', 0);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * @return array<int, string|int>
     */
    private function validRow(): array
    {
        return ['huile', 'Huile moteur', 'SKU-STD', 'L', 100, 180, 20, 5];
    }

    /**
     * Build an .xlsx upload with the product header row followed by $rows.
     *
     * @param  list<array<int, string|int|null>>  $rows
     */
    private function xlsx(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'test_products_');

        $writer = new Writer();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(ProductImportService::HEADERS));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return new UploadedFile(
            $path,
            'produits.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}

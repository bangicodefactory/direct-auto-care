<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Import\ProductImportService;
use App\Services\Import\VehicleImportService;
use App\Support\Import\SpreadsheetTemplate;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportTemplateTest extends TestCase
{
    use RefreshDatabase;

    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        // Isolate the template cache in a unique temp dir so tests never touch
        // real storage and can't race each other (e.g. under parallel runs).
        $this->cacheDir = sys_get_temp_dir().'/import-tpl-test-'.uniqid();
        config(['import.template_cache_dir' => $this->cacheDir]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->cacheDir);
        parent::tearDown();
    }

    public function test_build_command_writes_both_templates(): void
    {
        $this->artisan('import:build-templates')->assertSuccessful();

        $this->assertFileExists(SpreadsheetTemplate::cachedPath(ProductImportService::TEMPLATE_FILENAME));
        $this->assertFileExists(SpreadsheetTemplate::cachedPath(VehicleImportService::TEMPLATE_FILENAME));
    }

    public function test_download_lazily_builds_the_cache_when_missing(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $cache = SpreadsheetTemplate::cachedPath(ProductImportService::TEMPLATE_FILENAME);
        $this->assertFileDoesNotExist($cache);

        $this->actingAs($admin)->get(route('products.import.template'))->assertOk();

        // Built on demand and — crucially — NOT deleted after send (it's a cache,
        // not a per-request temp file; this guards against reintroducing
        // deleteFileAfterSend, which is what invoked openspout every request).
        $this->assertFileExists($cache);
    }

    public function test_download_serves_the_existing_cache_without_regenerating(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $cache = SpreadsheetTemplate::cachedPath(ProductImportService::TEMPLATE_FILENAME);
        File::ensureDirectoryExists(dirname($cache));
        File::put($cache, 'SENTINEL-NOT-A-REAL-XLSX');

        $this->actingAs($admin)->get(route('products.import.template'))->assertOk();

        // The controller streamed the existing cached bytes verbatim — it did not
        // regenerate the template (which would have replaced the sentinel).
        $this->assertSame('SENTINEL-NOT-A-REAL-XLSX', File::get($cache));
    }

    public function test_rebuild_overwrites_a_stale_cached_template(): void
    {
        $cache = SpreadsheetTemplate::cachedPath(ProductImportService::TEMPLATE_FILENAME);
        File::ensureDirectoryExists(dirname($cache));
        File::put($cache, 'STALE');

        app(ProductImportService::class)->rebuildTemplate();

        // The stale copy was atomically replaced by a real .xlsx (zip magic "PK").
        $this->assertNotSame('STALE', File::get($cache));
        $this->assertSame('PK', substr(File::get($cache), 0, 2));
    }
}

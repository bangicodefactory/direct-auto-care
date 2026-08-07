<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProductUnitMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_08_07_202849_normalize_products_unit.php';

    /**
     * Re-running the migration's up() directly is safe: it is idempotent by
     * design (canonical values map onto themselves).
     */
    private function runMigration(): void
    {
        (require base_path(self::MIGRATION))->up();
    }

    private function insertProductWithRawUnit(string $unit, ?string $deletedAt = null): int
    {
        return DB::table('products')->insertGetId([
            'category'            => 'piece',
            'name'                => "Produit {$unit}",
            'sku'                 => null,
            'unit'                => $unit,
            'price_buy'           => null,
            'price_sell'          => 100,
            'quantity_on_hand'    => 1,
            'low_stock_threshold' => 1,
            'deleted_at'          => $deletedAt,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    public function test_legacy_spellings_are_normalized_to_canonical_units(): void
    {
        $cases = [
            'L'       => 'litre',
            'Litres ' => 'litre',
            'pièce'   => 'unite',
            'PCS'     => 'unite',
            'unité'   => 'unite',
            'boîte'   => 'boite',
            'paire'   => 'paire',
        ];

        $ids = [];
        foreach (array_keys($cases) as $raw) {
            $ids[$raw] = $this->insertProductWithRawUnit($raw);
        }

        $this->runMigration();

        foreach ($cases as $raw => $canonical) {
            $this->assertSame(
                $canonical,
                DB::table('products')->where('id', $ids[$raw])->value('unit'),
                "Expected '{$raw}' to normalize to '{$canonical}'",
            );
        }
    }

    public function test_unrecognized_units_are_coerced_to_unite_with_a_warning(): void
    {
        Log::spy();

        $id = $this->insertProductWithRawUnit('tonneau');

        $this->runMigration();

        $this->assertSame('unite', DB::table('products')->where('id', $id)->value('unit'));

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context) => str_contains($message, 'non reconnu')
                && $context['original'] === 'tonneau'
                && $context['rows'] === 1)
            ->once();
    }

    public function test_soft_deleted_products_are_normalized_too(): void
    {
        $id = $this->insertProductWithRawUnit('L', now()->toDateTimeString());

        $this->runMigration();

        $this->assertSame('litre', DB::table('products')->where('id', $id)->value('unit'));
    }

    public function test_migration_is_idempotent(): void
    {
        $id = $this->insertProductWithRawUnit('L');

        $this->runMigration();
        $this->runMigration();

        $this->assertSame('litre', DB::table('products')->where('id', $id)->value('unit'));
        $this->assertSame(1, Product::withTrashed()->count());
    }
}

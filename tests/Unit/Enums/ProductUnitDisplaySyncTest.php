<?php

namespace Tests\Unit\Enums;

use App\Enums\ProductUnit;
use PHPUnit\Framework\TestCase;

/**
 * ProductUnit::quantityLabel() (PDF) and UNIT_DISPLAY in resources/js/lib/utils.ts
 * (React pages) must render the same singular/plural forms for every unit. This
 * test parses the TypeScript map so the two tables cannot drift silently.
 */
class ProductUnitDisplaySyncTest extends TestCase
{
    /**
     * @return array<string, array{singular: string, plural: string}>
     */
    private function jsUnitDisplay(): array
    {
        $source = file_get_contents(dirname(__DIR__, 3) . '/resources/js/lib/utils.ts');
        $this->assertNotFalse($source, 'resources/js/lib/utils.ts must be readable');

        $this->assertSame(
            1,
            preg_match('/const UNIT_DISPLAY[^=]*=\s*\{(.*?)\n\};/s', $source, $block),
            'UNIT_DISPLAY map not found in utils.ts',
        );

        preg_match_all(
            "/^\s*(\w+):\s*\{\s*singular:\s*'([^']*)',\s*plural:\s*'([^']*)'\s*\}/mu",
            $block[1],
            $rows,
            PREG_SET_ORDER,
        );

        $map = [];
        foreach ($rows as [, $key, $singular, $plural]) {
            $map[$key] = ['singular' => $singular, 'plural' => $plural];
        }

        return $map;
    }

    public function test_php_and_js_unit_tables_have_the_same_keys(): void
    {
        $js  = array_keys($this->jsUnitDisplay());
        $php = array_map(fn (ProductUnit $u) => $u->value, ProductUnit::cases());

        sort($js);
        sort($php);

        $this->assertSame($php, $js);
    }

    public function test_php_and_js_unit_tables_render_the_same_labels(): void
    {
        $js = $this->jsUnitDisplay();

        foreach (ProductUnit::cases() as $unit) {
            $this->assertArrayHasKey($unit->value, $js);
            $this->assertSame($js[$unit->value]['singular'], $unit->quantityLabel(1), "{$unit->value} singular");
            $this->assertSame($js[$unit->value]['plural'], $unit->quantityLabel(2), "{$unit->value} plural");
        }
    }
}

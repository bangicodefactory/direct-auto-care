<?php

namespace Tests\Unit\Enums;

use App\Enums\ProductUnit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit labels live once, in `ui.units` of each lang/{locale}/ui.php: the PDF
 * reads them through ProductUnit::quantityLabel() and the React pages through
 * the shared `translations` prop. This keeps every locale's table in step with
 * the enum, so a new case cannot ship without its labels.
 */
class ProductUnitDisplaySyncTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function localeProvider(): array
    {
        return [
            'fr' => ['fr'],
            'ar' => ['ar'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function unitTable(string $locale): array
    {
        $ui = require dirname(__DIR__, 3) . "/lang/{$locale}/ui.php";

        $this->assertIsArray($ui['units'] ?? null, "lang/{$locale}/ui.php must define `units`");

        return $ui['units'];
    }

    #[DataProvider('localeProvider')]
    public function test_locale_labels_every_unit_and_nothing_else(string $locale): void
    {
        $keys = array_keys($this->unitTable($locale));
        $enum = array_map(fn (ProductUnit $u) => $u->value, ProductUnit::cases());

        sort($keys);
        sort($enum);

        $this->assertSame($enum, $keys);
    }

    #[DataProvider('localeProvider')]
    public function test_locale_has_both_forms_for_every_unit(string $locale): void
    {
        foreach ($this->unitTable($locale) as $unit => $forms) {
            foreach (['one', 'other'] as $form) {
                $this->assertIsString($forms[$form] ?? null, "{$locale} {$unit}.{$form}");
                $this->assertNotSame('', $forms[$form], "{$locale} {$unit}.{$form}");
            }
        }
    }
}

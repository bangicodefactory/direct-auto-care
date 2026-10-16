<?php

namespace Tests\Unit\Enums;

use App\Enums\ProductCategory;
use App\Enums\ProductUnit;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductUnitTest extends TestCase
{
    // -------------------------------------------------------------------------
    // tryFromSynonym — recognized spellings
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{string, ProductUnit}>
     */
    public static function synonymProvider(): array
    {
        return [
            'abbreviation L'           => ['L', ProductUnit::Litre],
            'padded and plural'        => [' Litres ', ProductUnit::Litre],
            'lowercase l'              => ['l', ProductUnit::Litre],
            'accented piece'           => ['pièce', ProductUnit::Unite],
            'uppercase PCS'            => ['PCS', ProductUnit::Unite],
            'accented unité'           => ['unité', ProductUnit::Unite],
            'canonical unite'          => ['unite', ProductUnit::Unite],
            'accented boîte'           => ['boîte', ProductUnit::Boite],
            'abbreviation bte'         => ['bte', ProductUnit::Boite],
            'ml lowercase'             => ['ml', ProductUnit::Millilitre],
            'kilo'                     => ['kilo', ProductUnit::Kg],
            'irregular plural jeux'    => ['jeux', ProductUnit::Jeu],
            'paires'                   => ['paires', ProductUnit::Paire],
            'kits'                     => ['kits', ProductUnit::Kit],
            'bidons'                   => ['bidons', ProductUnit::Bidon],
            'flacons'                  => ['flacons', ProductUnit::Flacon],
        ];
    }

    #[DataProvider('synonymProvider')]
    public function test_recognized_spellings_resolve_to_their_canonical_unit(string $raw, ProductUnit $expected): void
    {
        $this->assertSame($expected, ProductUnit::tryFromSynonym($raw));
    }

    // -------------------------------------------------------------------------
    // tryFromSynonym — unrecognized input
    // -------------------------------------------------------------------------

    public function test_unknown_spellings_resolve_to_null(): void
    {
        $this->assertNull(ProductUnit::tryFromSynonym('tonneau'));
        $this->assertNull(ProductUnit::tryFromSynonym(''));
        $this->assertNull(ProductUnit::tryFromSynonym(null));
    }

    public function test_grams_are_deliberately_not_mapped_to_kg(): void
    {
        // Coercing grams to kilograms would silently change what the
        // quantity means — 500 g is not 500 kg.
        $this->assertNull(ProductUnit::tryFromSynonym('g'));
        $this->assertNull(ProductUnit::tryFromSynonym('gramme'));
    }

    // -------------------------------------------------------------------------
    // defaultFor
    // -------------------------------------------------------------------------

    public function test_every_category_has_a_default_unit(): void
    {
        $this->assertSame(ProductUnit::Litre, ProductUnit::defaultFor(ProductCategory::Huile));
        $this->assertSame(ProductUnit::Unite, ProductUnit::defaultFor(ProductCategory::Filtre));
        $this->assertSame(ProductUnit::Unite, ProductUnit::defaultFor(ProductCategory::Piece));
        $this->assertSame(ProductUnit::Unite, ProductUnit::defaultFor(ProductCategory::Autre));
    }

    // -------------------------------------------------------------------------
    // quantityLabel — reads ui.units from lang/{locale}/ui.php
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{ProductUnit, string, string}>
     */
    public static function quantityLabelProvider(): array
    {
        return [
            'unite'      => [ProductUnit::Unite, 'unité', 'unités'],
            'litre'      => [ProductUnit::Litre, 'L', 'L'],
            'millilitre' => [ProductUnit::Millilitre, 'mL', 'mL'],
            'kg'         => [ProductUnit::Kg, 'kg', 'kg'],
            'bidon'      => [ProductUnit::Bidon, 'bidon', 'bidons'],
            'flacon'     => [ProductUnit::Flacon, 'flacon', 'flacons'],
            'boite'      => [ProductUnit::Boite, 'boîte', 'boîtes'],
            'paire'      => [ProductUnit::Paire, 'paire', 'paires'],
            'jeu'        => [ProductUnit::Jeu, 'jeu', 'jeux'],
            'kit'        => [ProductUnit::Kit, 'kit', 'kits'],
        ];
    }

    #[DataProvider('quantityLabelProvider')]
    public function test_quantity_label_is_singular_up_to_one_and_plural_above(
        ProductUnit $unit,
        string $singular,
        string $plural,
    ): void {
        $this->assertSame($singular, $unit->quantityLabel(0));
        $this->assertSame($singular, $unit->quantityLabel(1));
        $this->assertSame($singular, $unit->quantityLabel(0.5));
        $this->assertSame($singular, $unit->quantityLabel(-1));
        $this->assertSame($plural, $unit->quantityLabel(1.5));
        $this->assertSame($plural, $unit->quantityLabel(2));
        $this->assertSame($plural, $unit->quantityLabel(-2));
    }

    public function test_quantity_label_follows_the_locale(): void
    {
        App::setLocale('ar');

        $this->assertSame('زوج', ProductUnit::Paire->quantityLabel(1));
        $this->assertSame('أزواج', ProductUnit::Paire->quantityLabel(2));
    }

    public function test_every_case_has_a_quantity_label(): void
    {
        foreach (ProductUnit::cases() as $unit) {
            $this->assertNotSame('', $unit->quantityLabel(1));
            $this->assertNotSame('', $unit->quantityLabel(2));
        }
    }

    // -------------------------------------------------------------------------
    // Canonical values are their own synonyms (migration idempotence relies on it)
    // -------------------------------------------------------------------------

    public function test_every_canonical_value_resolves_to_itself(): void
    {
        foreach (ProductUnit::cases() as $unit) {
            $this->assertSame($unit, ProductUnit::tryFromSynonym($unit->value));
        }
    }
}

<?php

namespace App\Enums;

/**
 * Stock unit of a product.
 *
 * Validation-boundary type only, on purpose: Product does NOT cast `unit` to
 * this enum. A cast would turn any legacy free-text value still in the column
 * (mid-deploy, or after a backup restore) into a ValueError that 500s every
 * page rendering products; uncast, such a value degrades to raw text on
 * screen and is caught by validation on the next write.
 *
 * Case order is the dropdown order — most common first.
 */
enum ProductUnit: string
{
    case Unite = 'unite';
    case Litre = 'litre';
    case Millilitre = 'millilitre';
    case Kg = 'kg';
    case Bidon = 'bidon';
    case Flacon = 'flacon';
    case Boite = 'boite';
    case Paire = 'paire';
    case Jeu = 'jeu';
    case Kit = 'kit';

    /**
     * Common free-text spellings mapped to canonical values. Keys are
     * pre-normalized (trimmed, lowercased, accents stripped).
     *
     * `g`/`gramme` is deliberately absent: coercing grams to kg would silently
     * change what the quantity means. Unknown values are the caller's problem.
     *
     * The unit-normalization data migration inlines its own frozen copy of
     * this map on purpose — do not try to share them.
     *
     * @var array<string, string>
     */
    private const SYNONYMS = [
        'unite'        => 'unite',
        'unites'       => 'unite',
        'u'            => 'unite',
        'pc'           => 'unite',
        'pcs'          => 'unite',
        'piece'        => 'unite',
        'pieces'       => 'unite',
        'l'            => 'litre',
        'litre'        => 'litre',
        'litres'       => 'litre',
        'lt'           => 'litre',
        'ltr'          => 'litre',
        'ml'           => 'millilitre',
        'millilitre'   => 'millilitre',
        'millilitres'  => 'millilitre',
        'kg'           => 'kg',
        'kilo'         => 'kg',
        'kilos'        => 'kg',
        'kilogramme'   => 'kg',
        'kilogrammes'  => 'kg',
        'bidon'        => 'bidon',
        'bidons'       => 'bidon',
        'flacon'       => 'flacon',
        'flacons'      => 'flacon',
        'boite'        => 'boite',
        'boites'       => 'boite',
        'bte'          => 'boite',
        'paire'        => 'paire',
        'paires'       => 'paire',
        'pr'           => 'paire',
        'jeu'          => 'jeu',
        'jeux'         => 'jeu',
        'kit'          => 'kit',
        'kits'         => 'kit',
    ];

    public function label(): string
    {
        return match ($this) {
            self::Unite => 'Unité',
            self::Litre => 'Litre (L)',
            self::Millilitre => 'Millilitre (mL)',
            self::Kg => 'Kilogramme (kg)',
            self::Bidon => 'Bidon',
            self::Flacon => 'Flacon',
            self::Boite => 'Boîte',
            self::Paire => 'Paire',
            self::Jeu => 'Jeu',
            self::Kit => 'Kit',
        };
    }

    /**
     * Short display form for a given quantity ("2 jeux", "1 jeu", "5 L").
     * Singular when |quantity| <= 1, plural otherwise. Mirrors UNIT_DISPLAY
     * in resources/js/lib/utils.ts — keep both in sync.
     */
    public function quantityLabel(float $quantity): string
    {
        [$singular, $plural] = match ($this) {
            self::Unite => ['unité', 'unités'],
            self::Litre => ['L', 'L'],
            self::Millilitre => ['mL', 'mL'],
            self::Kg => ['kg', 'kg'],
            self::Bidon => ['bidon', 'bidons'],
            self::Flacon => ['flacon', 'flacons'],
            self::Boite => ['boîte', 'boîtes'],
            self::Paire => ['paire', 'paires'],
            self::Jeu => ['jeu', 'jeux'],
            self::Kit => ['kit', 'kits'],
        };

        return abs($quantity) <= 1 ? $singular : $plural;
    }

    /**
     * Resolve a free-text unit ("L", " Litres ", "pièce", "PCS") to its
     * canonical case, or null when the spelling is not recognized.
     */
    public static function tryFromSynonym(?string $raw): ?self
    {
        if ($raw === null) {
            return null;
        }

        $key = strtr(mb_strtolower(trim($raw)), [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o',
            'û' => 'u', 'ü' => 'u', 'ù' => 'u',
            'ç' => 'c',
        ]);

        $canonical = self::SYNONYMS[$key] ?? null;

        return $canonical === null ? null : self::from($canonical);
    }

    /**
     * The unit pre-selected when a product of this category is created.
     */
    public static function defaultFor(ProductCategory $category): self
    {
        return match ($category) {
            ProductCategory::Huile => self::Litre,
            ProductCategory::Filtre,
            ProductCategory::Piece,
            ProductCategory::Autre => self::Unite,
        };
    }
}

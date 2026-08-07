<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class () extends Migration {
    /**
     * Frozen copy of ProductUnit's synonym map — inlined on purpose so this
     * migration keeps working verbatim even if the enum is refactored later.
     * Keys are pre-normalized (trimmed, lowercased, accents stripped).
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

    /**
     * Normalize every products.unit value (soft-deleted rows included) to the
     * canonical ProductUnit vocabulary, ahead of validation going strict.
     *
     * Idempotent — canonical values map onto themselves — and deliberately
     * unable to fail on surprise data: an unrecognized value is coerced to
     * 'unite' with a warning in the log rather than aborting the deploy.
     * Coercion (not skipping) because the product form's Select cannot render
     * a value outside the enum, which would leave such products uneditable.
     */
    public function up(): void
    {
        // Deduplicated in PHP, NOT via SQL DISTINCT: the column's collation
        // (utf8mb4_unicode_ci) is accent-insensitive, so DISTINCT collapses
        // « unité » and « unite » into one representative — and when that
        // representative happens to be the canonical spelling, the accented
        // rows would silently skip normalization. PHP uniqueness is byte-exact.
        $distinctUnits = DB::table('products')->pluck('unit')->unique();

        foreach ($distinctUnits as $raw) {
            $key = strtr(mb_strtolower(trim((string) $raw)), [
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                'à' => 'a', 'â' => 'a',
                'î' => 'i', 'ï' => 'i',
                'ô' => 'o',
                'û' => 'u', 'ü' => 'u', 'ù' => 'u',
                'ç' => 'c',
            ]);

            $canonical = self::SYNONYMS[$key] ?? null;

            if ($canonical === null) {
                Log::warning('products.unit non reconnu lors de la normalisation, remplacé par « unite »', [
                    'original' => $raw,
                    'rows'     => DB::table('products')->where('unit', $raw)->count(),
                ]);

                $canonical = 'unite';
            }

            if ($canonical !== $raw) {
                DB::table('products')->where('unit', $raw)->update(['unit' => $canonical]);
            }
        }
    }

    /**
     * No-op: the original free-text spellings are not recoverable.
     */
    public function down(): void
    {
        //
    }
};

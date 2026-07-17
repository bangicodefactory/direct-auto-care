<?php

namespace App\Services\Import;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Support\Import\ImportReport;
use App\Support\Import\SpreadsheetReader;
use App\Support\Import\SpreadsheetTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Unique;

/**
 * Bulk-imports products from an uploaded .xlsx. Each row is validated against the
 * same field rules as the single-record form ({@see StoreProductRequest::fieldRules()}).
 * `quantity_on_hand` is written directly (no stock movement, matching
 * {@see \App\Http\Controllers\ProductsController::store()}). Valid rows are inserted
 * atomically; invalid rows are collected into an {@see ImportReport} (partial import).
 */
class ProductImportService
{
    private const MAX_ROWS = 2000;

    /** Download filename, also the key under the pre-built template cache. */
    public const TEMPLATE_FILENAME = 'modele_produits.xlsx';

    /** @var list<string> */
    public const HEADERS = [
        'category', 'name', 'sku', 'unit', 'price_buy',
        'price_sell', 'quantity_on_hand', 'low_stock_threshold',
    ];

    /**
     * Path to the pre-built template, building it on the fly only if the cache is
     * missing (normally warmed at deploy time by `import:build-templates`). Serving
     * this instead of {@see template()} keeps openspout off the request hot path.
     */
    public function cachedTemplatePath(): string
    {
        $path = SpreadsheetTemplate::cachedPath(self::TEMPLATE_FILENAME);

        return is_file($path) ? $path : $this->rebuildTemplate();
    }

    /** Rebuild the cached template from scratch and return its path. */
    public function rebuildTemplate(): string
    {
        return SpreadsheetTemplate::persist(self::TEMPLATE_FILENAME, $this->template());
    }

    public function template(): string
    {
        return SpreadsheetTemplate::build(
            self::HEADERS,
            ['huile', 'Huile moteur 5W-30 5L', 'HUI-5W30-5L', 'L', 180, 260, 24, 5],
            [
                'category doit être l\'une des valeurs : huile, filtre, piece, autre.',
                'sku : optionnel mais unique s\'il est renseigné.',
                'price_buy : optionnel. price_sell : obligatoire.',
                'quantity_on_hand : entier ≥ 0 (stock initial).',
                'low_stock_threshold : entier ≥ 1 (seuil d\'alerte stock bas).',
                'Astuce : formatez la colonne sku en « Texte » dans Excel pour préserver les zéros initiaux.',
            ],
        );
    }

    public function import(UploadedFile $file): ImportReport
    {
        $report = new ImportReport();
        $parsed = SpreadsheetReader::read($file->getRealPath(), self::MAX_ROWS);

        if ($parsed['truncated']) {
            $report->markTruncated();
        }

        // Drop the DB `unique` rule from the per-row validator: it would fire one
        // query per row. Existing SKUs are preloaded once and checked in-memory
        // below (the DB unique index remains the final backstop on insert).
        $rules = StoreProductRequest::fieldRules();
        $rules['sku'] = $this->withoutUniqueRules($rules['sku']);

        // Include soft-deleted rows: the DB unique index on `sku` is global, so a
        // row reusing a trashed SKU must be reported here rather than crash the
        // atomic insert with a UniqueConstraintViolationException.
        $existingSkus = array_fill_keys(
            Product::withTrashed()->whereNotNull('sku')->pluck('sku')->all(),
            true,
        );

        $seenSkus = [];
        $valid = [];

        foreach ($parsed['rows'] as $entry) {
            $data = $this->only($entry['data'], self::HEADERS);
            $errors = [];

            $validator = Validator::make($data, $rules);
            foreach ($validator->errors()->all() as $message) {
                $errors[] = $message;
            }

            $sku = $data['sku'] ?? null;
            if ($sku !== null) {
                if (isset($existingSkus[$sku])) {
                    $errors[] = "La référence « {$sku} » est déjà utilisée.";
                } elseif (isset($seenSkus[$sku])) {
                    $errors[] = "La référence « {$sku} » est en double dans le fichier.";
                }
            }

            if ($errors !== []) {
                $report->addFailure($entry['row'], $errors);

                continue;
            }

            if ($sku !== null) {
                $seenSkus[$sku] = true;
            }

            $valid[] = $this->withoutNulls($data);
        }

        if ($valid !== []) {
            DB::transaction(function () use ($valid): void {
                foreach ($valid as $attributes) {
                    Product::create($attributes);
                }
            });
            $report->markImported(count($valid));
        }

        return $report;
    }

    /**
     * Strip any {@see Unique} rule from a field's rule list so per-row validation
     * doesn't hit the database; uniqueness is enforced in-memory from a preloaded set.
     *
     * @param  array<int, mixed>  $rules
     * @return array<int, mixed>
     */
    private function withoutUniqueRules(array $rules): array
    {
        return array_values(array_filter(
            $rules,
            static fn ($rule): bool => ! $rule instanceof Unique,
        ));
    }

    /**
     * @param  array<string, string|null>  $data
     * @param  list<string>  $keys
     * @return array<string, string|null>
     */
    private function only(array $data, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $data[$key] ?? null;
        }

        return $out;
    }

    /**
     * @param  array<string, string|null>  $attributes
     * @return array<string, string>
     */
    private function withoutNulls(array $attributes): array
    {
        return array_filter($attributes, static fn ($value): bool => $value !== null);
    }
}

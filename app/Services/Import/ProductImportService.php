<?php

namespace App\Services\Import;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Models\User;
use App\Support\Import\ImportReport;
use App\Support\Import\SpreadsheetReader;
use App\Support\Import\SpreadsheetTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

    /** @var list<string> */
    public const HEADERS = [
        'category', 'name', 'sku', 'unit', 'price_buy',
        'price_sell', 'quantity_on_hand', 'low_stock_threshold',
    ];

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
            ],
        );
    }

    public function import(UploadedFile $file, User $user): ImportReport
    {
        $report = new ImportReport();
        $parsed = SpreadsheetReader::read($file->getRealPath(), self::MAX_ROWS);

        if ($parsed['truncated']) {
            $report->markTruncated();
        }

        $seenSkus = [];
        $valid = [];

        foreach ($parsed['rows'] as $entry) {
            $data = $this->only($entry['data'], self::HEADERS);
            $errors = [];

            $validator = Validator::make($data, StoreProductRequest::fieldRules());
            foreach ($validator->errors()->all() as $message) {
                $errors[] = $message;
            }

            $sku = $data['sku'] ?? null;
            if ($sku !== null && isset($seenSkus[$sku])) {
                $errors[] = "Le SKU « {$sku} » est en double dans le fichier.";
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

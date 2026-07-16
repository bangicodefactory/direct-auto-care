<?php

namespace App\Services\Import;

use App\Http\Requests\StoreVehicleRequest;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Support\Import\ImportReport;
use App\Support\Import\SpreadsheetReader;
use App\Support\Import\SpreadsheetTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Unique;

/**
 * Bulk-imports vehicles from an uploaded .xlsx. Each row is validated against the
 * same field rules as the single-record form ({@see StoreVehicleRequest::fieldRules()}),
 * its owner resolved by customer_id or customer_phone. Valid rows are inserted
 * atomically; invalid rows are collected into an {@see ImportReport} (partial import).
 */
class VehicleImportService
{
    private const MAX_ROWS = 2000;

    /** @var list<string> */
    public const HEADERS = [
        'customer_id', 'customer_phone', 'make', 'model', 'year',
        'license_plate', 'vin', 'mileage', 'fuel_type', 'color',
    ];

    /** @var list<string> */
    private const ATTRIBUTES = ['make', 'model', 'year', 'license_plate', 'vin', 'mileage', 'fuel_type', 'color'];

    public function template(): string
    {
        return SpreadsheetTemplate::build(
            self::HEADERS,
            ['', '+212600000000', 'Dacia', 'Logan', 2020, '12345-A-6', '', 120000, 'essence', 'Gris'],
            [
                'Renseignez customer_id OU customer_phone — l\'identifiant du client propriétaire.',
                'fuel_type doit être l\'une des valeurs : essence, diesel, hybride, electrique.',
                'year : un entier entre 1950 et '.(date('Y') + 1).'.',
                'vin : exactement 17 caractères si renseigné, sinon laissez vide.',
                'license_plate : obligatoire et unique.',
                'mileage et color sont optionnels.',
                'Astuce : formatez les colonnes license_plate et vin en « Texte » dans Excel pour éviter toute conversion numérique.',
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

        [$idSet, $phoneMap] = $this->customerLookups();

        // Drop the DB `unique` rules from the per-row validator (one query per row
        // otherwise); existing plates/VINs are preloaded once and checked in-memory
        // below (the DB unique indexes remain the final backstop on insert).
        $rules = StoreVehicleRequest::fieldRules();
        $rules['license_plate'] = $this->withoutUniqueRules($rules['license_plate']);
        $rules['vin'] = $this->withoutUniqueRules($rules['vin']);

        // Include soft-deleted rows: the DB unique indexes on `license_plate`/`vin`
        // are global, so a row reusing a trashed identifier must be reported here
        // rather than crash the atomic insert with a UniqueConstraintViolationException.
        $existingPlates = array_fill_keys(Vehicle::withTrashed()->pluck('license_plate')->all(), true);
        $existingVins = array_fill_keys(
            Vehicle::withTrashed()->whereNotNull('vin')->pluck('vin')->all(),
            true,
        );

        $seenPlates = [];
        $seenVins = [];
        $valid = [];

        foreach ($parsed['rows'] as $entry) {
            $data = $entry['data'];
            $errors = [];

            $customerId = $this->resolveCustomer($data, $idSet, $phoneMap, $errors);

            $attributes = $this->only($data, self::ATTRIBUTES);
            $validator = Validator::make($attributes, $rules);
            foreach ($validator->errors()->all() as $message) {
                $errors[] = $message;
            }

            $plate = $attributes['license_plate'] ?? null;
            if ($plate !== null) {
                if (isset($existingPlates[$plate])) {
                    $errors[] = "La plaque « {$plate} » est déjà utilisée.";
                } elseif (isset($seenPlates[$plate])) {
                    $errors[] = "La plaque « {$plate} » est en double dans le fichier.";
                }
            }

            $vin = $attributes['vin'] ?? null;
            if ($vin !== null) {
                if (isset($existingVins[$vin])) {
                    $errors[] = "Le VIN « {$vin} » est déjà utilisé.";
                } elseif (isset($seenVins[$vin])) {
                    $errors[] = "Le VIN « {$vin} » est en double dans le fichier.";
                }
            }

            if ($errors !== []) {
                $report->addFailure($entry['row'], $errors);

                continue;
            }

            if ($plate !== null) {
                $seenPlates[$plate] = true;
            }
            if ($vin !== null) {
                $seenVins[$vin] = true;
            }

            $valid[] = array_merge(['customer_id' => $customerId], $this->withoutNulls($attributes));
        }

        if ($valid !== []) {
            DB::transaction(function () use ($valid): void {
                foreach ($valid as $attributes) {
                    Vehicle::create($attributes);
                }
            });
            $report->markImported(count($valid));
        }

        return $report;
    }

    /**
     * @return array{0: array<int, true>, 1: array<string, list<int>>}
     */
    private function customerLookups(): array
    {
        $idSet = [];
        $phoneMap = [];

        foreach (Customer::query()->get(['id', 'phone']) as $customer) {
            $idSet[$customer->id] = true;
            $phone = trim((string) $customer->phone);
            if ($phone !== '') {
                $phoneMap[$phone][] = $customer->id;
            }
        }

        return [$idSet, $phoneMap];
    }

    /**
     * Resolve the owning customer by id (priority) then phone. Appends a French
     * error and returns null when unresolved or ambiguous.
     *
     * @param  array<string, string|null>  $data
     * @param  array<int, true>  $idSet
     * @param  array<string, list<int>>  $phoneMap
     * @param  list<string>  $errors
     */
    private function resolveCustomer(array $data, array $idSet, array $phoneMap, array &$errors): ?int
    {
        $rawId = $data['customer_id'] ?? null;
        $rawPhone = $data['customer_phone'] ?? null;

        if ($rawId !== null && $rawId !== '') {
            $id = (int) $rawId;
            if (! isset($idSet[$id])) {
                $errors[] = "Client introuvable pour customer_id « {$rawId} ».";

                return null;
            }

            return $id;
        }

        if ($rawPhone !== null && $rawPhone !== '') {
            $matches = $phoneMap[trim($rawPhone)] ?? [];
            if (count($matches) === 0) {
                $errors[] = "Aucun client avec le téléphone « {$rawPhone} ».";

                return null;
            }
            // Defensive: `customers.phone` is unique today, so this branch is
            // currently unreachable — kept in case that constraint is relaxed.
            if (count($matches) > 1) {
                $errors[] = "Plusieurs clients ont le téléphone « {$rawPhone} » — précisez customer_id.";

                return null;
            }

            return $matches[0];
        }

        $errors[] = 'customer_id ou customer_phone est requis.';

        return null;
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

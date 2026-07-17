<?php

namespace App\Support\Import;

use OpenSpout\Reader\XLSX\Reader;

/**
 * Thin wrapper over openspout that reads the first sheet of an uploaded .xlsx
 * into associative rows keyed by the (normalised) header cells. Cell values are
 * coerced to trimmed strings (or null) so the caller can validate them with the
 * standard Laravel string/numeric rules.
 */
class SpreadsheetReader
{
    /**
     * @return array{rows: list<array{row: int, data: array<string, string|null>}>, truncated: bool}
     */
    public static function read(string $path, int $limit): array
    {
        $reader = new Reader();
        $reader->open($path);

        /** @var list<string>|null $headers */
        $headers = null;
        $rows = [];
        $truncated = false;
        $rowNumber = 0;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;
                    $cells = array_map([self::class, 'stringify'], $row->toArray());

                    if ($headers === null) {
                        $headers = array_map(
                            static fn ($h): string => strtolower(trim((string) $h)),
                            $cells,
                        );

                        continue;
                    }

                    // Skip fully-blank rows so trailing empty lines don't error.
                    if (! array_filter($cells, static fn ($c): bool => $c !== null && $c !== '')) {
                        continue;
                    }

                    if (count($rows) >= $limit) {
                        $truncated = true;

                        break;
                    }

                    $data = [];
                    foreach ($headers as $col => $key) {
                        // Skip unnamed columns and let the first occurrence of a
                        // duplicated header win rather than the last.
                        if ($key === '' || array_key_exists($key, $data)) {
                            continue;
                        }
                        $data[$key] = $cells[$col] ?? null;
                    }

                    $rows[] = ['row' => $rowNumber, 'data' => $data];
                }

                break; // Only the first sheet holds import data.
            }
        } finally {
            $reader->close();
        }

        return ['rows' => $rows, 'truncated' => $truncated];
    }

    private static function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}

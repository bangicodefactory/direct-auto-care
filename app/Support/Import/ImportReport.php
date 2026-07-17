<?php

namespace App\Support\Import;

/**
 * Result of a bulk import: how many rows were created and, for a partial import,
 * which rows were skipped and why. Serialized to the Inertia flash payload so the
 * frontend can render a per-row error table.
 */
class ImportReport
{
    private int $imported = 0;

    /** @var list<array{row: int, errors: list<string>}> */
    private array $failed = [];

    /** Whether the file exceeded the row cap and was truncated. */
    private bool $truncated = false;

    public function markImported(int $count = 1): void
    {
        $this->imported += $count;
    }

    /**
     * @param  list<string>  $errors
     */
    public function addFailure(int $row, array $errors): void
    {
        $this->failed[] = ['row' => $row, 'errors' => array_values($errors)];
    }

    public function markTruncated(): void
    {
        $this->truncated = true;
    }

    public function importedCount(): int
    {
        return $this->imported;
    }

    public function failedCount(): int
    {
        return count($this->failed);
    }

    /**
     * @return array{imported: int, failed: list<array{row: int, errors: list<string>}>, truncated: bool}
     */
    public function toArray(): array
    {
        return [
            'imported'  => $this->imported,
            'failed'    => $this->failed,
            'truncated' => $this->truncated,
        ];
    }
}

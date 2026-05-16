<?php

namespace App\Services;

use App\Enums\InvoiceType;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceNumberingService
{
    /**
     * Return the next sequential invoice number for the given type + fiscal year.
     *
     * MUST be called inside an open DB::transaction(). The caller is responsible
     * for inserting the Invoice row and committing before releasing control —
     * the SELECT … FOR UPDATE lock is the only guard against concurrent duplicates.
     *
     * Numbers are zero-padded to 5 digits (max 99,999 per type per fiscal year).
     * MAX() comparison is lexicographic — correct only because of the fixed-width padding.
     *
     * Note: the transaction-level guard fires in production but not inside RefreshDatabase
     * tests (the test framework's own outer transaction satisfies the level check).
     */
    public function next(InvoiceType $type, int $fiscalYear): string
    {
        throw_if(
            DB::transactionLevel() === 0,
            \LogicException::class,
            'InvoiceNumberingService::next() must be called inside a DB::transaction().',
        );

        $max = Invoice::withTrashed()
            ->where('type', $type->value)
            ->where('fiscal_year', $fiscalYear)
            ->lockForUpdate()
            ->max('number');

        if ($max !== null && ! preg_match('/-(\d{5})$/', $max, $m)) {
            throw new \UnexpectedValueException("Unrecognised invoice number format: {$max}");
        }

        $sequence = isset($m[1]) ? (int) $m[1] + 1 : 1;

        return sprintf(
            '%s-%d-%05d',
            match ($type) {
                InvoiceType::Invoice => 'FAC',
                InvoiceType::Quote   => 'DEV',
            },
            $fiscalYear,
            $sequence,
        );
    }
}

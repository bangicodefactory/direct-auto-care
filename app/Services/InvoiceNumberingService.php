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
     * We use ORDER BY number DESC LIMIT 1 FOR UPDATE (not MAX()) so that InnoDB
     * has a concrete row to lock exclusively. MAX() is a post-fetch aggregate —
     * InnoDB never acquires a row lock on its result, so concurrent transactions
     * all see NULL and race to claim sequence 1. first() gives InnoDB either:
     *   • the physical row with the highest number (exclusive row lock), or
     *   • a gap lock on the (type, fiscal_year) index range when no rows exist,
     *     blocking concurrent inserts until this transaction commits.
     *
     * Numbers are zero-padded to 5 digits (max 99,999 per type per fiscal year).
     * Lexicographic ORDER BY is correct only because of the fixed-width padding.
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

        $latest = Invoice::withTrashed()
            ->where('type', $type->value)
            ->where('fiscal_year', $fiscalYear)
            ->orderByDesc('number')
            ->lockForUpdate()
            ->first(['number']);

        if ($latest !== null && ! preg_match('/-(\d{5})$/', $latest->number, $m)) {
            throw new \UnexpectedValueException("Unrecognised invoice number format: {$latest->number}");
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

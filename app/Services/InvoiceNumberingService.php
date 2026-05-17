<?php

namespace App\Services;

use App\Enums\InvoiceType;
use Illuminate\Support\Facades\DB;

class InvoiceNumberingService
{
    /**
     * Return the next sequential invoice number for the given type + fiscal year.
     *
     * MUST be called inside an open DB::transaction(). The caller is responsible
     * for inserting the Invoice row and committing before releasing control.
     *
     * Sequencing is backed by the `invoice_sequences` table rather than scanning
     * the `invoices` table. This guarantees a true exclusive row lock (X) rather
     * than a gap lock, which is the only correct guard against concurrent duplicates.
     *
     * InnoDB gap locks are COMPATIBLE between readers — multiple transactions can
     * simultaneously hold gap locks on the same empty range and race to claim
     * sequence 1. An X lock on an existing row is exclusive: only one transaction
     * can hold it at a time, serialising concurrent callers without gaps.
     *
     * insertOrIgnore() seeds the row on first use; subsequent calls lock the existing
     * row and atomically increment last_sequence.
     *
     * Numbers are zero-padded to 5 digits (max 99,999 per type per fiscal year).
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

        // Ensure the sequence row exists. When two transactions race here on an
        // empty table, InnoDB lets one insert succeed and silently rejects the
        // other's duplicate — both then proceed to lockForUpdate() below.
        DB::table('invoice_sequences')->insertOrIgnore([
            'type'          => $type->value,
            'fiscal_year'   => $fiscalYear,
            'last_sequence' => 0,
        ]);

        // Exclusively lock the sequence row. Only one transaction can hold this
        // X lock at a time — all concurrent callers queue behind it.
        $current = DB::table('invoice_sequences')
            ->where('type', $type->value)
            ->where('fiscal_year', $fiscalYear)
            ->lockForUpdate()
            ->value('last_sequence');

        $next = $current + 1;

        DB::table('invoice_sequences')
            ->where('type', $type->value)
            ->where('fiscal_year', $fiscalYear)
            ->update(['last_sequence' => $next]);

        return sprintf(
            '%s-%d-%05d',
            match ($type) {
                InvoiceType::Invoice => 'FAC',
                InvoiceType::Quote   => 'DEV',
            },
            $fiscalYear,
            $next,
        );
    }
}

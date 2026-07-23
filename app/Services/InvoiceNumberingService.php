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
     * Sequencing is backed by the `invoice_sequences` table. The single statement:
     *
     *   INSERT … VALUES (1) ON DUPLICATE KEY UPDATE last_sequence = last_sequence + 1
     *
     * atomically inserts (first use) or increments (subsequent use) the counter,
     * and acquires an **exclusive X lock** on the row in both cases.
     *
     * Unlike INSERT IGNORE, which acquires a shared (S) lock on the conflicting row
     * and causes a deadlock when multiple transactions race to upgrade S → X, the
     * ON DUPLICATE KEY UPDATE path acquires X directly — no upgrade, no deadlock.
     *
     * The X lock is held for the duration of the transaction. No concurrent transaction
     * can read or modify the row until this transaction commits, so the subsequent
     * SELECT is safe without FOR UPDATE.
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

        // INSERT acquires X on the new row; ON DUPLICATE KEY UPDATE acquires X on the
        // existing row. Either way this transaction exclusively owns the row from here
        // until commit — no S→X upgrade race, no deadlock.
        DB::statement(
            'INSERT INTO invoice_sequences (type, fiscal_year, last_sequence) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE last_sequence = last_sequence + 1',
            [$type->value, $fiscalYear],
        );

        // The X lock is still held, so no concurrent transaction can have changed
        // last_sequence between the statement above and this read.
        $sequence = DB::table('invoice_sequences')
            ->where('type', $type->value)
            ->where('fiscal_year', $fiscalYear)
            ->value('last_sequence');

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

    /**
     * Roll the counter back by one IF the given number is the tail of its
     * sequence, so a just-created mistake can be deleted without burning a
     * fiscal number. A mid-sequence number can never be reclaimed (that would
     * require renumbering every later document) — the counter is left alone
     * and the deletion simply leaves a gap.
     *
     * MUST be called inside an open DB::transaction(). The conditional UPDATE
     * is atomic and takes an X lock on the sequence row, so a concurrent
     * next() cannot interleave between the tail check and the decrement.
     *
     * Returns true when the counter was rolled back — the caller must then
     * free the number on the soft-deleted row (the unique index on
     * invoices.number spans trashed rows, so an unrenamed trashed row would
     * collide with the reissued number).
     */
    public function release(InvoiceType $type, int $fiscalYear, string $number): bool
    {
        throw_if(
            DB::transactionLevel() === 0,
            \LogicException::class,
            'InvoiceNumberingService::release() must be called inside a DB::transaction().',
        );

        $sequence = (int) substr($number, strrpos($number, '-') + 1);

        $affected = DB::update(
            'UPDATE invoice_sequences SET last_sequence = last_sequence - 1 WHERE type = ? AND fiscal_year = ? AND last_sequence = ?',
            [$type->value, $fiscalYear, $sequence],
        );

        return $affected === 1;
    }
}

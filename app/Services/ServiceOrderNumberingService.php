<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ServiceOrderNumberingService
{
    private const TYPE = 'service_order';

    /**
     * Return the next sequential service-order number for the given fiscal year.
     *
     * Mirrors InvoiceNumberingService exactly: backed by the shared
     * `invoice_sequences` table (type = 'service_order'), using a single
     * INSERT … ON DUPLICATE KEY UPDATE that acquires an exclusive X lock on the
     * row — no S→X upgrade race, no deadlock. Must run inside a DB::transaction().
     *
     * Format: OS-{year}-{seq:05d} (max 99,999 per fiscal year).
     */
    public function next(int $fiscalYear): string
    {
        throw_if(
            DB::transactionLevel() === 0,
            \LogicException::class,
            'ServiceOrderNumberingService::next() must be called inside a DB::transaction().',
        );

        DB::statement(
            'INSERT INTO invoice_sequences (type, fiscal_year, last_sequence) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE last_sequence = last_sequence + 1',
            [self::TYPE, $fiscalYear],
        );

        $sequence = DB::table('invoice_sequences')
            ->where('type', self::TYPE)
            ->where('fiscal_year', $fiscalYear)
            ->value('last_sequence');

        return sprintf('OS-%d-%05d', $fiscalYear, $sequence);
    }
}

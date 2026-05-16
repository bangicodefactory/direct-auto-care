<?php

namespace App\Services;

use App\Enums\InvoiceType;
use App\Models\Invoice;

class InvoiceNumberingService
{
    /**
     * Return the next sequential invoice number for the given type + fiscal year.
     *
     * MUST be called inside an open DB::transaction(). The caller is responsible
     * for inserting the Invoice row and committing before releasing control —
     * the SELECT … FOR UPDATE lock is the only guard against concurrent duplicates.
     */
    public function next(InvoiceType $type, int $fiscalYear): string
    {
        $max = Invoice::withTrashed()
            ->where('type', $type->value)
            ->where('fiscal_year', $fiscalYear)
            ->lockForUpdate()
            ->max('number');

        $sequence = $max === null ? 1 : (int) last(explode('-', $max)) + 1;

        return sprintf(
            '%s-%d-%05d',
            $type === InvoiceType::Invoice ? 'FAC' : 'DEV',
            $fiscalYear,
            $sequence,
        );
    }
}

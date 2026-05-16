<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft     = 'draft';
    case Issued    = 'issued';
    case Paid      = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Brouillon',
            self::Issued    => 'Émise',
            self::Paid      => 'Payée',
            self::Cancelled => 'Annulée',
        };
    }
}

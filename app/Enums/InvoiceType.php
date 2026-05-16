<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Quote   = 'quote';
    case Invoice = 'invoice';

    public function label(): string
    {
        return match ($this) {
            self::Quote   => 'Devis',
            self::Invoice => 'Facture',
        };
    }
}

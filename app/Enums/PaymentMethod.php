<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash     = 'cash';
    case Cheque   = 'cheque';
    case Transfer = 'transfer';
    case Card     = 'card';

    public function label(): string
    {
        return match ($this) {
            self::Cash     => 'Espèces',
            self::Cheque   => 'Chèque',
            self::Transfer => 'Virement',
            self::Card     => 'Carte bancaire',
        };
    }
}

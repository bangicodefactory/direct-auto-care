<?php

namespace App\Enums;

enum ProductCategory: string
{
    case Huile  = 'huile';
    case Filtre = 'filtre';
    case Piece  = 'piece';
    case Autre  = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Huile  => 'Huile',
            self::Filtre => 'Filtre',
            self::Piece  => 'Pièce',
            self::Autre  => 'Autre',
        };
    }
}

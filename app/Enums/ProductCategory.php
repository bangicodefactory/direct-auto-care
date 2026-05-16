<?php

namespace App\Enums;

enum ProductCategory: string
{
    case Huile  = 'huile';
    case Filtre = 'filtre';
    case Piece  = 'piece';
    case Autre  = 'autre';
}

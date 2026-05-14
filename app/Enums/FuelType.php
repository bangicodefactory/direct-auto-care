<?php

namespace App\Enums;

enum FuelType: string
{
    case Essence   = 'essence';
    case Diesel    = 'diesel';
    case Hybride   = 'hybride';
    case Electrique = 'electrique';
}

<?php

namespace App\Enums;

enum ReminderType: string
{
    case Vidange             = 'vidange';
    case VisiteTechnique     = 'visite_technique';
    case EntretienPeriodique = 'entretien_periodique';

    public function label(): string
    {
        return match ($this) {
            self::Vidange             => 'Vidange',
            self::VisiteTechnique     => 'Visite technique',
            self::EntretienPeriodique => 'Entretien périodique',
        };
    }
}

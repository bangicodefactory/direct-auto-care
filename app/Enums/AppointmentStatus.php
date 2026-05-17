<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'En attente',
            self::Confirmed => 'Confirmé',
            self::Completed => 'Terminé',
            self::Cancelled => 'Annulé',
        };
    }
}

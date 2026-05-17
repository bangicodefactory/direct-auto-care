<?php

namespace App\Enums;

enum ReminderStatus: string
{
    case Pending      = 'pending';
    case Sent         = 'sent';
    case Acknowledged = 'acknowledged';
    case Cancelled    = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending      => 'En attente',
            self::Sent         => 'Envoyé',
            self::Acknowledged => 'Accusé de réception',
            self::Cancelled    => 'Annulé',
        };
    }
}

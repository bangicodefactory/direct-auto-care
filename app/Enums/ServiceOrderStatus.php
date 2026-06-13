<?php

namespace App\Enums;

enum ServiceOrderStatus: string
{
    case Reception      = 'reception';
    case Diagnosis      = 'diagnosis';
    case QuoteSent      = 'quote_sent';
    case InProgress     = 'in_progress';
    case QualityControl = 'quality_control';
    case Notified       = 'notified';
    case ReadyPickup    = 'ready_pickup';
    case Closed         = 'closed';
    case Cancelled      = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Reception      => 'Réception',
            self::Diagnosis      => 'Diagnostic',
            self::QuoteSent      => 'Devis envoyé',
            self::InProgress     => 'En cours',
            self::QualityControl => 'Contrôle qualité',
            self::Notified       => 'Client notifié',
            self::ReadyPickup    => 'Prêt à récupérer',
            self::Closed         => 'Clôturé',
            self::Cancelled      => 'Annulé',
        };
    }

    /**
     * Tailwind colour token used by status badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Reception      => 'gray',
            self::Diagnosis      => 'blue',
            self::QuoteSent      => 'yellow',
            self::InProgress     => 'orange',
            self::QualityControl => 'purple',
            self::Notified       => 'cyan',
            self::ReadyPickup    => 'green',
            self::Closed         => 'green',
            self::Cancelled      => 'red',
        };
    }

    /**
     * The next phase in the linear workflow, or null at the end / for terminal states.
     *
     * Reception → Diagnosis → QuoteSent → InProgress → QualityControl
     *           → Notified → ReadyPickup → Closed
     */
    public function nextStatus(): ?self
    {
        return match ($this) {
            self::Reception      => self::Diagnosis,
            self::Diagnosis      => self::QuoteSent,
            self::QuoteSent      => self::InProgress,
            self::InProgress     => self::QualityControl,
            self::QualityControl => self::Notified,
            self::Notified       => self::ReadyPickup,
            self::ReadyPickup    => self::Closed,
            self::Closed, self::Cancelled => null,
        };
    }

    /**
     * The phase-timestamp column that records when this status was entered.
     */
    public function timestampColumn(): string
    {
        return match ($this) {
            self::Reception      => 'received_at',
            self::Diagnosis      => 'diagnosed_at',
            self::QuoteSent      => 'quote_sent_at',
            self::InProgress     => 'in_progress_at',
            self::QualityControl => 'quality_control_at',
            self::Notified       => 'notified_at',
            self::ReadyPickup    => 'ready_pickup_at',
            self::Closed         => 'closed_at',
            self::Cancelled      => 'cancelled_at',
        };
    }
}

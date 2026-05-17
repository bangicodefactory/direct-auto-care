<?php

namespace App\Exceptions;

class AppointmentOverlapException extends \RuntimeException
{
    public function __construct(string $message = 'Ce véhicule a déjà un rendez-vous sur ce créneau.')
    {
        parent::__construct($message);
    }
}

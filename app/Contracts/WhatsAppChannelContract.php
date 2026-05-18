<?php

namespace App\Contracts;

use App\Models\Reminder;

interface WhatsAppChannelContract
{
    /**
     * Send a WhatsApp template message for the given reminder.
     *
     * Returns the provider message ID on success, or null on failure.
     * On failure, populates $reminder->error_message with the provider error code.
     */
    public function send(Reminder $reminder): ?string;
}

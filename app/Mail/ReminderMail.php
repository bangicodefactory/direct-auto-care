<?php

namespace App\Mail;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Reminder $reminder)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->reminder->type->label() . ' — Direct Auto Care',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reminder',
        );
    }


}

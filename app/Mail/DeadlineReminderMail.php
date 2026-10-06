<?php

namespace App\Mail;

use App\Models\CourtEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeadlineReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CourtEvent $event,
        public string $offsetLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Podsjetnik: '.$this->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.deadline-reminder',
        );
    }
}

<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceOverdueMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public int $daysAfterDue,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Opomena za račun '.$this->invoice->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.invoice-overdue',
        );
    }
}

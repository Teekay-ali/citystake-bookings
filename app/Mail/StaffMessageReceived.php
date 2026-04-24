<?php

namespace App\Mail;

use App\Models\BookingMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BookingMessage $message) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Guest message — ' . $this->message->booking->booking_reference,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.messages.staff-received');
    }

    public function attachments(): array { return []; }
}

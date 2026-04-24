<?php

namespace App\Mail;

use App\Models\BookingMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BookingMessage $message) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New message regarding your booking ' . $this->message->booking->booking_reference,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.messages.guest-received');
    }

    public function attachments(): array { return []; }
}

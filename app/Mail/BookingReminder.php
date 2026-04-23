<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Check-in Tomorrow at ' . $this->booking->building->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

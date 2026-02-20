<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CheckInReminder extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Check-in Reminder - Tomorrow at ' . $this->booking->building->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.check-in-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

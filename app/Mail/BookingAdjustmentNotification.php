<?php

namespace App\Mail;

use App\Models\BookingAdjustment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingAdjustmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BookingAdjustment $adjustment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Goodwill Adjustment for Your Booking - ' . $this->adjustment->booking->booking_reference,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.bookings.adjustment');
    }
}

<?php

namespace App\Mail;

use App\Models\Booking;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking Confirmation - ' . $this->booking->booking_reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmation',
        );
    }

    public function attachments(): array
    {
        try {
            $pdf = InvoiceService::generatePdfString($this->booking);

            return [
                Attachment::fromData(
                    fn () => $pdf,
                    'invoice-' . $this->booking->booking_reference . '.pdf'
                )->withMime('application/pdf'),
            ];
        } catch (\Exception $e) {
            // Never let a PDF failure block the confirmation email
            \Log::error('Invoice PDF generation failed for email', [
                'booking_reference' => $this->booking->booking_reference,
                'error'             => $e->getMessage(),
            ]);
            return [];
        }
    }
}

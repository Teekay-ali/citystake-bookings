<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Generate a PDF invoice and return as a raw string (for email attachment).
     */
    public static function generatePdfString(Booking $booking): string
    {
        $booking->loadMissing(['building', 'unitType', 'unit']);

        return Pdf::loadView('invoices.booking', compact('booking'))
            ->setPaper('a4', 'portrait')
            ->output();
    }

    /**
     * Return a download response (for direct route download).
     */
    public static function download(Booking $booking)
    {
        $booking->loadMissing(['building', 'unitType', 'unit']);

        $filename = 'invoice-' . $booking->booking_reference . '.pdf';

        return Pdf::loadView('invoices.booking', compact('booking'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}

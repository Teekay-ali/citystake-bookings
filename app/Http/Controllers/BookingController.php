<?php

namespace App\Http\Controllers;

use App\Mail\BookingCancelled;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Notifications\BookingCancelledNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::where('user_id', auth()->id())
            ->with(['building.images', 'unitType.images', 'unit'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($booking) {
                if ($booking->status === 'cancelled') return 'cancelled';
                if ($booking->status === 'paused') return 'paused';
                if ($booking->check_out < now()) return 'past';
                if ($booking->check_in <= now() && $booking->check_out >= now()) return 'active';
                return 'upcoming';
            });

        return Inertia::render('Booking/Index', [
            'bookings' => [
                'upcoming'  => $bookings->get('upcoming', collect()),
                'active'    => $bookings->get('active',   collect()),
                'past'      => $bookings->get('past',      collect()),
                'cancelled' => $bookings->get('cancelled', collect()),
                'paused'    => $bookings->get('paused',    collect()),
            ],
        ]);
    }

    public function show(Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $booking->load(['building.images', 'unitType.images', 'unit', 'messages.sender']);

        return Inertia::render('Booking/Show', [
            'booking' => $booking,
        ]);
    }

    public function cancel(Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$booking->canBeCancelled()) {
            return redirect()->back()->with('error', 'This booking cannot be cancelled.');
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status'                    => 'cancelled',
                'cancelled_at'              => now(),
                // Reset late checkout state — fee is void on cancellation
                'late_checkout_requested'   => false,
                'late_checkout_status'      => null,
                'late_checkout_fee'         => null,
                'late_checkout_approved_by' => null,
                'late_checkout_approved_at' => null,
                'late_checkout_settled_at'  => null,
            ]);

            AuditLog::log('booking.cancelled', $booking,
                ['status' => 'confirmed'],
                ['status' => 'cancelled']
            );
        });

        // Notifications outside the transaction so network I/O doesn't hold DB locks
        try {
            Mail::to($booking->guest_email)->send(new BookingCancelled($booking));
        } catch (\Exception $e) {
            Log::error('Cancellation email failed for booking ' . $booking->booking_reference, ['error' => $e->getMessage()]);
        }

        $recipients = NotificationService::getUsersByRoles(['manager', 'receptionist'], $booking->building_id);
        NotificationService::send($recipients, new BookingCancelledNotification($booking));

        return redirect()->route('bookings.index')
            ->with('success', 'Booking cancelled. Any refund due will be arranged by our team.');
    }
}

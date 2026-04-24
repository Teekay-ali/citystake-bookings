<?php

namespace App\Http\Controllers;

use App\Mail\GuestMessageReceived;
use App\Mail\StaffMessageReceived;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingMessageController extends Controller
{
    /**
     * Guest sends a message
     */
    public function guestSend(Request $request, Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'body' => 'required|string|min:1|max:2000',
        ]);

        $message = BookingMessage::create([
            'booking_id'  => $booking->id,
            'sender_id'   => auth()->id(),
            'sender_type' => 'guest',
            'body'        => $request->body,
        ]);

        $message->load(['booking.building', 'booking.unit', 'booking.unitType']);

        // Notify staff by email
        try {
            $adminEmail = config('mail.admin_email', 'admin@citystake.com');
            Mail::to($adminEmail)->send(new StaffMessageReceived($message));
        } catch (\Exception $e) {
            Log::error('Failed to send staff message notification', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Message sent.');
    }

    /**
     * Staff sends a reply
     */
    public function staffSend(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);

        $request->validate([
            'body' => 'required|string|min:1|max:2000',
        ]);

        $message = BookingMessage::create([
            'booking_id'  => $booking->id,
            'sender_id'   => auth()->id(),
            'sender_type' => 'staff',
            'body'        => $request->body,
        ]);

        $message->load(['booking', 'sender']);

        // Mark all guest messages on this booking as read
        BookingMessage::where('booking_id', $booking->id)
            ->where('sender_type', 'guest')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Notify guest by email
        try {
            Mail::to($booking->guest_email)->send(new GuestMessageReceived($message));
        } catch (\Exception $e) {
            Log::error('Failed to send guest message notification', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Reply sent.');
    }

    /**
     * Admin messages index — all conversations with unread counts
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);

        $user        = auth()->user();
        $buildingIds = $user->hasGlobalAccess()
            ? null
            : $user->accessibleBuildingIds();

        $conversations = Booking::whereHas('messages')
            ->when($buildingIds, fn($q) => $q->whereIn('building_id', $buildingIds))
            ->with([
                'building:id,name',
                'unitType:id,name',
                'messages' => fn($q) => $q->latest()->limit(1),
            ])
            ->withCount([
                'messages as unread_count' => fn($q) => $q
                    ->where('sender_type', 'guest')
                    ->whereNull('read_at'),
            ])
            ->orderByDesc(
                BookingMessage::select('created_at')
                    ->whereColumn('booking_id', 'bookings.id')
                    ->latest()
                    ->limit(1)
            )
            ->paginate(20);

        return inertia('Admin/Messages/Index', [
            'conversations' => $conversations,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingEnquiry;
use App\Models\Building;
use App\Models\UnitType;
use App\Notifications\NewEnquiryNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class EnquiryController extends Controller
{
    /**
     * Show the "Request to Book" form for a unit type.
     */
    public function create(Request $request, Building $building, UnitType $unitType)
    {
        $request->validate([
            'check_in'  => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests'    => 'required|integer|min:1|max:' . $unitType->max_guests,
        ]);

        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        // Show an estimated price (not binding — reception finalises)
        $estimate = new Booking([
            'check_in'  => $request->check_in,
            'check_out' => $request->check_out,
            'guests'    => $request->guests,
        ]);
        $estimate->calculateTotal($unitType);

        return Inertia::render('Booking/Create', [
            'building'    => $building->load('images'),
            'unitType'    => $unitType->load('images'),
            'bookingData' => [
                'check_in'         => $request->check_in,
                'check_out'        => $request->check_out,
                'guests'           => $request->guests,
                'nights'           => $estimate->nights,
                'subtotal'         => $estimate->subtotal,
                'discount_type'    => $estimate->discount_type,
                'discount_percent' => $estimate->discount_percent,
                'discount_amount'  => $estimate->discount_amount,
                'total_amount'     => $estimate->total_amount,
            ],
        ]);
    }

    /**
     * Store a booking enquiry (no payment) and notify reception.
     */
    public function store(Request $request, Building $building, UnitType $unitType)
    {
        $validated = $request->validate([
            'check_in'         => 'required|date|after_or_equal:today',
            'check_out'        => 'required|date|after:check_in',
            'guests'           => 'required|integer|min:1|max:' . $unitType->max_guests,
            'guest_name'       => 'required|string|max:255',
            'guest_email'      => 'required|email|max:255',
            'guest_phone'      => 'required|string|max:30',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        $enquiry = BookingEnquiry::create([
            'building_id'      => $building->id,
            'unit_type_id'     => $unitType->id,
            'check_in'         => $validated['check_in'],
            'check_out'        => $validated['check_out'],
            'guests'           => $validated['guests'],
            'guest_name'       => $validated['guest_name'],
            'guest_email'      => $validated['guest_email'],
            'guest_phone'      => $validated['guest_phone'],
            'special_requests' => $validated['special_requests'] ?? null,
            'status'           => 'new',
        ]);

        $enquiry->loadMissing('building', 'unitType');

        $recipients = NotificationService::getUsersByRoles(['receptionist', 'manager'], $building->id);
        Notification::send($recipients, new NewEnquiryNotification($enquiry));

        return redirect()->route('enquiries.thank-you');
    }

    public function thankYou()
    {
        return Inertia::render('Booking/EnquiryThankYou');
    }
}

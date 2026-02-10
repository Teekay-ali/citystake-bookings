<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function create(Request $request, Property $property): Response
    {
        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:' . $property->max_guests,
        ]);

        // Check availability
        if (!$property->isAvailable($request->check_in, $request->check_out)) {
            return redirect()->back()->with('error', 'Property is not available for selected dates');
        }

        // Calculate pricing
        $booking = new Booking([
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'guests' => $request->guests,
        ]);
        $booking->calculateTotal($property);

        return Inertia::render('Booking/Create', [
            'property' => $property->load('primaryImage'),
            'bookingData' => [
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
                'guests' => $request->guests,
                'nights' => $booking->nights,
                'subtotal' => $booking->subtotal,
                'cleaning_fee' => $booking->cleaning_fee,
                'service_charge' => $booking->service_charge,
                'total_amount' => $booking->total_amount,
            ],
        ]);
    }

    public function store(Request $request, Property $property)
    {
        $validated = $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:' . $property->max_guests,
            'special_requests' => 'nullable|string|max:500',
        ]);

        try {
            $booking = DB::transaction(function () use ($validated, $property) {
                // Lock property to prevent double booking
                $property = Property::lockForUpdate()->findOrFail($property->id);

                if (!$property->isAvailable($validated['check_in'], $validated['check_out'])) {
                    throw new \Exception('Property is no longer available for selected dates');
                }

                $booking = new Booking([
                    'booking_reference' => Booking::generateReference(),
                    'property_id' => $property->id,
                    'user_id' => auth()->id(),
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'guests' => $validated['guests'],
                    'special_requests' => $validated['special_requests'] ?? null,
                ]);

                $booking->calculateTotal($property);
                $booking->save();

                return $booking;
            });

            // TODO: Initialize Paystack payment here (Phase 3)
            // For now, redirect to confirmation
            return redirect()->route('bookings.confirmation', $booking)
                ->with('success', 'Booking created successfully! Proceed to payment.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function confirmation(Booking $booking): Response
    {
        // Ensure user can only view their own booking
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $booking->load(['property.primaryImage']);

        return Inertia::render('Booking/Confirmation', [
            'booking' => $booking,
        ]);
    }
}

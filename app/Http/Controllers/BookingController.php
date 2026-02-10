<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Building;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function create(Request $request, Building $building, UnitType $unitType): Response
    {
        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:' . $unitType->max_guests,
        ]);

        // Check if unit type belongs to building
        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        // Check availability
        if (!$unitType->hasAvailability($request->check_in, $request->check_out)) {
            return redirect()->back()->with('error', 'No units available for selected dates');
        }

        // Calculate pricing
        $booking = new Booking([
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'guests' => $request->guests,
        ]);
        $booking->calculateTotal($unitType);

        return Inertia::render('Booking/Create', [
            'building' => $building->load('primaryImage'),
            'unitType' => $unitType,
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

    public function store(Request $request, Building $building, UnitType $unitType)
    {
        $validated = $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:' . $unitType->max_guests,
            'special_requests' => 'nullable|string|max:500',
        ]);

        // Check if unit type belongs to building
        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        try {
            $booking = DB::transaction(function () use ($validated, $building, $unitType) {
                // Lock unit type to prevent double booking
                $unitType = UnitType::lockForUpdate()->findOrFail($unitType->id);

                // Find an available unit (Option A: Auto-assign)
                $availableUnit = $unitType->findAvailableUnit($validated['check_in'], $validated['check_out']);

                if (!$availableUnit) {
                    throw new \Exception('No units available for selected dates');
                }

                $booking = new Booking([
                    'booking_reference' => Booking::generateReference(),
                    'building_id' => $building->id,
                    'unit_type_id' => $unitType->id,
                    'unit_id' => $availableUnit->id,
                    'user_id' => auth()->id(),
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'guests' => $validated['guests'],
                    'special_requests' => $validated['special_requests'] ?? null,
                ]);

                $booking->calculateTotal($unitType);
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

        $booking->load(['building.primaryImage', 'unitType', 'unit']);

        return Inertia::render('Booking/Confirmation', [
            'booking' => $booking,
        ]);
    }
}

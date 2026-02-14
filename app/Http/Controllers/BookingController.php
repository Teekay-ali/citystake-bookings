<?php

namespace App\Http\Controllers;

use App\Services\PaystackService;

use App\Models\Booking;
use App\Models\Building;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function create(Request $request, Building $building, UnitType $unitType)
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
            return redirect()->back()->with('error', 'Sorry, no units are available for the selected dates. Please try different dates.');
        }

        // Calculate pricing
        $booking = new Booking([
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'guests' => $request->guests,
        ]);
        $booking->calculateTotal($unitType);

        return Inertia::render('Booking/Create', [
            'building' => $building->load('images'),
            'unitType' => $unitType->load('images'),
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
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'required|string|max:20',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        // Check if unit type belongs to building
        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        try {
            $booking = DB::transaction(function () use ($validated, $building, $unitType) {
                // Lock unit type to prevent double booking
                $unitType = UnitType::lockForUpdate()->findOrFail($unitType->id);

                // Find an available unit
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
                    'guest_name' => $validated['guest_name'],
                    'guest_email' => $validated['guest_email'],
                    'guest_phone' => $validated['guest_phone'],
                    'special_requests' => $validated['special_requests'] ?? null,
                ]);

                $booking->calculateTotal($unitType);
                $booking->save();

                return $booking;
            });

            // Redirect to payment page
            return redirect()->route('bookings.payment', $booking->booking_reference)
                ->with('success', 'Booking created successfully! Proceed to payment.');

        }

        catch (\Exception $e) {
            \Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('error', 'Unable to create booking: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function payment($bookingReference)
    {
        $booking = Booking::where('booking_reference', $bookingReference)
            ->with(['building.images', 'unitType.images', 'unit'])
            ->firstOrFail();

        // Ensure user can only view their own booking
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        // If already paid, redirect to confirmation
        if ($booking->isPaid()) {
            return redirect()->route('bookings.confirmation', $booking);
        }

        $paystackService = new PaystackService();

        return Inertia::render('Booking/Payment', [
            'booking' => $booking,
            'paystackPublicKey' => $paystackService->getPublicKey(),
        ]);
    }

    public function verifyPayment(Request $request, $bookingReference)
    {
        $booking = Booking::where('booking_reference', $bookingReference)->firstOrFail();

        // Ensure user can only verify their own booking
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('bookings.payment', $bookingReference)
                ->with('error', 'Payment reference not found');
        }

        try {
            $paystackService = new PaystackService();
            $response = $paystackService->verifyTransaction($reference);

            \Log::info('Payment verification response', ['response' => $response]);

            // In verifyPayment method - update messages
            if ($response['status'] && $response['data']['status'] === 'success') {
                $booking->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                    'paystack_reference' => $reference,
                    'paid_at' => now(),
                ]);

                \Log::info('Redirecting with success message');

                return redirect()->route('bookings.confirmation', $booking)
                    ->with('success', '🎉 Payment successful! Your booking is confirmed.');
            } else {

                \Log::info('Payment verification failed', ['response' => $response]);

                return redirect()->route('bookings.payment', $bookingReference)
                    ->with('error', 'Payment verification failed. Please contact support if amount was debited.');
            }

        } catch (\Exception $e) {
            \Log::error('Payment verification error: ' . $e->getMessage());

            return redirect()->route('bookings.payment', $bookingReference)
                ->with('error', 'An error occurred while verifying payment.');
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

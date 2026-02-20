<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use App\Models\UnitType;
use App\Mail\BookingConfirmation;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['building', 'unitType', 'unit', 'user']);

        // Filter by status
        if ($request->status) {
            if ($request->status === 'active') {
                $query->where('status', 'confirmed')
                    ->where('check_in', '<=', now())
                    ->where('check_out', '>=', now());
            } elseif ($request->status === 'upcoming') {
                $query->where('status', 'confirmed')
                    ->where('check_in', '>', now());
            } elseif ($request->status === 'past') {
                $query->where('check_out', '<', now());
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by building
        if ($request->building) {
            $query->where('building_id', $request->building);
        }

        // Filter by payment status
        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        // Search by booking reference or guest name
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('booking_reference', 'like', '%' . $request->search . '%')
                    ->orWhere('guest_name', 'like', '%' . $request->search . '%')
                    ->orWhere('guest_email', 'like', '%' . $request->search . '%');
            });
        }

        // Sort
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $bookings = $query->paginate(20)->withQueryString();

        // Get buildings for filter
        $buildings = Building::select('id', 'name')->get();

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $bookings,
            'buildings' => $buildings,
            'filters' => [
                'status' => $request->status,
                'building' => $request->building,
                'payment_status' => $request->payment_status,
                'search' => $request->search,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
    }

    public function create()
    {
        $buildings = Building::with('unitTypes')->where('is_active', true)->get();

        return Inertia::render('Admin/Bookings/Create', [
            'buildings' => $buildings,
        ]);
    }

    public function storeAdminBooking(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'unit_type_id' => 'required|exists:unit_types,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'required|string|max:20',
            'special_requests' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:cash,pos,bank_transfer',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        try {
            $building = Building::findOrFail($validated['building_id']);
            $unitType = UnitType::findOrFail($validated['unit_type_id']);

            // Verify unit type belongs to building
            if ($unitType->building_id !== $building->id) {
                return redirect()->back()
                    ->with('error', 'Invalid unit type for selected building.')
                    ->withInput();
            }

            // Business rules validation
            $checkIn = Carbon::parse($validated['check_in'])->startOfDay();
            $checkOut = Carbon::parse($validated['check_out'])->startOfDay();

            if ($checkIn->isBefore(now()->startOfDay())) {
                return redirect()->back()
                    ->with('error', 'Check-in date cannot be in the past.')
                    ->withInput();
            }

            $nights = $checkIn->diffInDays($checkOut);
            $minNights = config('booking.min_nights', 1);

            if ($nights < $minNights) {
                return redirect()->back()
                    ->with('error', "Minimum stay is {$minNights} night(s).")
                    ->withInput();
            }

            // Check availability
            if (!$unitType->hasAvailability($validated['check_in'], $validated['check_out'])) {
                return redirect()->back()
                    ->with('error', 'No units available for selected dates.')
                    ->withInput();
            }

            // Find available unit
            $availableUnit = $unitType->findAvailableUnit($validated['check_in'], $validated['check_out']);

            if (!$availableUnit) {
                return redirect()->back()
                    ->with('error', 'No units available for selected dates.')
                    ->withInput();
            }

            // Calculate pricing (reuse existing logic)
            $subtotal = $unitType->base_price_per_night * $nights;
            $serviceCharge = $subtotal * ($unitType->service_charge_percent / 100);
            $totalAmount = $subtotal + $unitType->cleaning_fee + $serviceCharge;

            // Create booking
            $booking = Booking::create([
                'booking_reference' => Booking::generateReference(),
                'building_id' => $building->id,
                'unit_type_id' => $unitType->id,
                'unit_id' => $availableUnit->id,
                'user_id' => null, // Admin-created bookings don't have user_id
                'created_by_admin_id' => auth()->id(), // Add this - the admin who created it
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'guests' => $validated['guests'],
                'nights' => $nights,
                'guest_name' => $validated['guest_name'],
                'guest_email' => $validated['guest_email'],
                'guest_phone' => $validated['guest_phone'],
                'special_requests' => $validated['special_requests'] ?? null,
                'subtotal' => $subtotal,
                'cleaning_fee' => $unitType->cleaning_fee,
                'service_charge' => $serviceCharge,
                'total_amount' => $totalAmount,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => $validated['payment_method'],
                'paystack_reference' => $validated['payment_reference'],
                'paid_at' => now(),
            ]);

            // Send confirmation email to guest
            Mail::to($booking->guest_email)->send(new BookingConfirmation($booking));

            return redirect()->route('admin.bookings.show', $booking->id)
                ->with('success', 'Booking created successfully!');

        } catch (\Exception $e) {
            \Log::error('Admin booking creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => auth()->id(),
                'data' => $validated ?? $request->all(),
            ]);

            return redirect()->back()
                ->with('error', 'Unable to create booking. Please try again.')
                ->withInput();
        }
    }

    public function show(Booking $booking)
    {
        $booking->load(['building', 'unitType', 'unit', 'user']);

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => $booking,
        ]);
    }
}

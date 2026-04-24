<?php

namespace App\Http\Controllers\Admin;

use App\Notifications\NewBookingNotification;
use App\Notifications\LateCheckoutRequestedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Notification;
use App\Models\FinancialTransaction;
use App\Traits\ScopedByBuilding;
use App\Services\DiscountService;
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
    use ScopedByBuilding;

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);

        $user  = auth()->user();
        $query = Booking::with(['building', 'unitType', 'unit', 'user']);

        if (!$user->hasGlobalAccess()) {
            $query->whereIn('building_id', $user->accessibleBuildingIds() ?? []);
        }

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
        $buildings = $this->accessibleBuildings()->select('id', 'name')->get();

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
        abort_unless(auth()->user()->can('create-bookings'), 403);

        $buildings = $this->accessibleBuildings()
            ->with(['unitTypes:id,building_id,name,bedroom_type,base_price_per_night,cleaning_fee,service_charge_percent,max_guests'])
            ->select('id', 'name')
            ->get();

        return Inertia::render('Admin/Bookings/Create', [
            'buildings' => $buildings,
        ]);
    }

    public function storeAdminBooking(Request $request)
    {
        abort_unless(auth()->user()->can('create-bookings'), 403);

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
            $subtotal      = $unitType->base_price_per_night * $nights;
            $serviceCharge = $subtotal * ($unitType->service_charge_percent / 100);
            $discount      = DiscountService::resolve($nights);
            $discountAmt   = $discount['percent'] > 0
                ? round($subtotal * ($discount['percent'] / 100), 2)
                : 0;
            $totalAmount   = ($subtotal - $discountAmt) + $unitType->cleaning_fee + $serviceCharge;

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
                'discount_type'    => $discount['type'],
                'discount_percent' => $discount['percent'],
                'discount_amount'  => $discountAmt,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => $validated['payment_method'],
                'paystack_reference' => $validated['payment_reference'],
                'paid_at' => now(),
            ]);

            FinancialTransaction::create([
                'building_id'      => $booking->building_id,
                'recorded_by'      => auth()->id(),
                'type'             => 'income',
                'category'         => 'booking',
                'reference_type'   => Booking::class,
                'reference_id'     => $booking->id,
                'description'      => "Walk-in booking {$booking->booking_reference} — {$booking->guest_name}",
                'amount'           => $booking->total_amount,
                'payment_method'   => $validated['payment_method'],
                'payment_reference'=> $validated['payment_reference'] ?? null,
                'transaction_date' => now()->toDateString(),
            ]);

            // Send confirmation email to guest
            Mail::to($booking->guest_email)->send(new BookingConfirmation($booking));

            $recipients = NotificationService::getUsersByRoles(['manager'], $booking->building_id);
            Notification::send($recipients, new NewBookingNotification($booking));

            return redirect()->route('manage.bookings.show', $booking->id)
                ->with('success', 'Booking created successfully!');

        } catch (\Exception $e) {
            \Log::error('Admin booking creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => auth()->id(),
                'data' => $validated ?? $request->all(),
            ]);

            return redirect()->back()
                ->with('error', 'Unable to create booking. Please try again or contact support.')
                ->withInput();
        }
    }

    public function show(Booking $booking)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);

        $booking->load([
            'building', 'unitType', 'unit', 'user',
            'checkedInBy', 'lateCheckoutApprovedBy',
            'messages.sender',
        ]);

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => array_merge($booking->toArray(), [
                'checked_in_by_name' => $booking->checkedInBy?->name,
                'unreadMessageCount' => $booking->messages()
                    ->where('sender_type', 'guest')
                    ->whereNull('read_at')
                    ->count(),
            ]),
        ]);
    }

    public function checkIn(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()->can('confirm-checkin'), 403);

        if (!$booking->canCheckIn()) {
            return back()->with('error', 'This booking cannot be checked in at this time.');
        }

        $validated = $request->validate([
            'amount_received'        => 'required|numeric|min:0',
            'checkin_payment_method' => 'required|in:cash,pos,bank_transfer,paystack',
            'checkin_notes'          => 'nullable|string|max:500',
        ]);

        $booking->update([
            'status'                 => 'checked_in',
            'checked_in_at'          => now(),
            'checked_in_by'          => auth()->id(),
            'amount_received'        => $validated['amount_received'],
            'checkin_payment_method' => $validated['checkin_payment_method'],
            'checkin_notes'          => $validated['checkin_notes'] ?? null,
        ]);

        return back()->with('success', 'Guest checked in successfully.');
    }

    public function requestLateCheckout(Booking $booking)
    {
        if (!$booking->canRequestLateCheckout()) {
            return back()->with('error', 'Late checkout cannot be requested for this booking.');
        }

        $fee = config('booking.late_checkout_fee', 20000);

        $booking->update([
            'late_checkout_requested' => true,
            'late_checkout_status'    => 'pending',
            'late_checkout_fee'       => $fee,
        ]);

        $recipients = NotificationService::getUsersByRoles(['manager'], $booking->building_id);
        Notification::send($recipients, new LateCheckoutRequestedNotification($booking));

        return back()->with('success', 'Late checkout requested. Awaiting manager approval.');
    }

    public function approveLateCheckout(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()->can('approve-late-checkout'), 403);

        if (!$booking->canApproveLateCheckout()) {
            return back()->with('error', 'This late checkout request cannot be approved.');
        }

        $validated = $request->validate([
            'action' => 'required|in:approved,rejected',
        ]);

        $booking->update([
            'late_checkout_status'       => $validated['action'],
            'late_checkout_approved_by'  => auth()->id(),
            'late_checkout_approved_at'  => now(),
            // Add fee to total only on approval
            'total_amount' => $validated['action'] === 'approved'
                ? $booking->total_amount + $booking->late_checkout_fee
                : $booking->total_amount,
        ]);

        $message = $validated['action'] === 'approved'
            ? 'Late checkout approved. Fee added to booking total.'
            : 'Late checkout request rejected.';

        return back()->with('success', $message);
    }

    public function settleLateCheckout(Booking $booking)
    {
        if (!$booking->canSettleLateCheckout()) {
            return back()->with('error', 'Late checkout fee has already been settled.');
        }

        $booking->update([
            'late_checkout_settled_at' => now(),
            'late_checkout_status'     => 'settled',
        ]);

        FinancialTransaction::create([
            'building_id'      => $booking->building_id,
            'recorded_by'      => auth()->id(),
            'type'             => 'income',
            'category'         => 'late_checkout',
            'reference_type'   => Booking::class,
            'reference_id'     => $booking->id,
            'description'      => "Late checkout fee — {$booking->guest_name} ({$booking->booking_reference})",
            'amount'           => $booking->late_checkout_fee,
            'payment_method'   => 'cash',
            'transaction_date' => now()->toDateString(),
        ]);

        return back()->with('success', 'Late checkout fee marked as settled.');
    }

    public function lateCheckoutRequests(Request $request)
    {
        $query = Booking::with(['building', 'unitType', 'unit'])
            ->where('late_checkout_requested', true);

        // Building scope
        $user = auth()->user();
        if (!$user->hasGlobalAccess()) {
            $query->whereIn('building_id', $user->accessibleBuildingIds());
        }

        // Filter by status
        if ($request->status) {
            $query->where('late_checkout_status', $request->status);
        } else {
            // Default: show pending first
            $query->orderByRaw("FIELD(late_checkout_status, 'pending', 'approved', 'settled', 'rejected')");
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/Bookings/LateCheckoutRequests', [
            'requests' => $requests,
            'filters'  => ['status' => $request->status],
            'counts'   => [
                'pending'  => Booking::scopedToUser($user)->where('late_checkout_status', 'pending')->count(),
                'approved' => Booking::scopedToUser($user)->where('late_checkout_status', 'approved')->count(),
            ],
        ]);
    }
}

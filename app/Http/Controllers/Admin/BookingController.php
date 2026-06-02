<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Notifications\CautionRefundProcessedNotification;
use App\Notifications\CautionRefundRequestedNotification;
use App\Notifications\GuestCheckedInNotification;
use App\Notifications\LateCheckoutDecisionNotification;
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
use App\Mail\GuestCheckedIn;
use App\Mail\GuestCheckedOut;
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
        $allowedSortColumns = [
            'created_at', 'check_in', 'check_out',
            'guest_name', 'total_amount', 'status', 'payment_status',
        ];
        $sortBy    = in_array($request->sort_by, $allowedSortColumns, true)
            ? $request->sort_by
            : 'created_at';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';

        $bookings = $query->latest()->paginate(10)->withQueryString();

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
            ->with([
                'unitTypes:id,building_id,name,bedroom_type,base_price_per_night,cleaning_fee,service_charge_percent,max_guests',
                'unitTypes.units:id,unit_type_id,unit_number,floor,status,is_available',
            ])
            ->select('id', 'name', 'caution_fee_amount', 'standard_checkout_time', 'late_checkout_fee_per_hour')
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
            'unit_id' => 'nullable|exists:units,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'required|string|max:20',
            'special_requests' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:pos,bank_transfer',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        try {
            $building = $this->accessibleBuildings()->findOrFail($validated['building_id']);
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

            // Check availability
            if (!$unitType->hasAvailability($validated['check_in'], $validated['check_out'])) {
                return redirect()->back()
                    ->with('error', 'No units available for selected dates.')
                    ->withInput();
            }

            // Use manually selected unit or auto-assign
            if (!empty($validated['unit_id'])) {
                $availableUnit = \App\Models\Unit::findOrFail($validated['unit_id']);

                // Verify the unit belongs to the selected unit type
                if ($availableUnit->unit_type_id !== $unitType->id) {
                    return redirect()->back()
                        ->with('error', 'Selected unit does not belong to the chosen unit type.')
                        ->withInput();
                }

                // Verify it's actually available for the dates
                $conflict = Booking::where('unit_id', $availableUnit->id)
                    ->whereNotIn('status', ['cancelled'])
                    ->where('check_in', '<', $validated['check_out'])
                    ->where('check_out', '>', $validated['check_in'])
                    ->exists();

                if ($conflict) {
                    return redirect()->back()
                        ->with('error', "Unit {$availableUnit->unit_number} is not available for the selected dates.")
                        ->withInput();
                }
            } else {
                $availableUnit = $unitType->findAvailableUnit($validated['check_in'], $validated['check_out']);

                if (!$availableUnit) {
                    return redirect()->back()
                        ->with('error', 'No units available for selected dates.')
                        ->withInput();
                }
            }

            // Calculate pricing (reuse existing logic)
            $subtotal      = $unitType->base_price_per_night * $nights;
            $serviceCharge = $subtotal * ($unitType->service_charge_percent / 100);
            $discount      = DiscountService::resolve($nights);
            $discountAmt   = $discount['percent'] > 0
                ? round($subtotal * ($discount['percent'] / 100), 2)
                : 0;
            $cautionFee = $nights === 1
                ? (float) $unitType->base_price_per_night
                : (float) ($building->caution_fee_amount ?? 70000);

            $totalAmount = ($subtotal - $discountAmt)
                + $unitType->cleaning_fee
                + $serviceCharge
                + $cautionFee;

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
                'caution_fee' => $cautionFee,
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
                'description'      => "Walk-in booking {$booking->booking_reference} - {$booking->guest_name}",
                'amount'           => $booking->total_amount,
                'payment_method'   => $validated['payment_method'],
                'payment_reference'=> $validated['payment_reference'] ?? null,
                'transaction_date' => now()->toDateString(),
            ]);

            AuditLog::log('booking.created', $booking, null, ['reference' => $booking->booking_reference, 'guest' => $booking->guest_name, 'method' => $validated['payment_method']]);

            // Send confirmation email to guest
            Mail::to($booking->guest_email)->send(new BookingConfirmation($booking));

            $recipients = NotificationService::getUsersByRoles(['manager'], $booking->building_id);
            Notification::send($recipients, new NewBookingNotification($booking));

            return redirect()->route('manage.bookings.show', $booking->id)
                ->with('success', 'Booking created successfully!')
                ->with('prompt_photo_id', true);

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
            'building', 'unitType', 'unit', 'user', 'adjustments',
            'checkedInBy', 'checkedOutBy', 'lateCheckoutApprovedBy',
            'messages.sender',
            'adjustments.appliedBy',
            'documents.uploadedBy',
        ]);

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => array_merge($booking->toArray(), [
                'checked_in_by_name'  => $booking->checkedInBy?->name,
                'checked_out_by_name' => $booking->checkedOutBy?->name,
                'unreadMessageCount'  => $booking->messages()
                    ->where('sender_type', 'guest')
                    ->whereNull('read_at')
                    ->count(),
                'documents' => $booking->documents->map(fn($d) => [
                    'id'             => $d->id,
                    'url'            => $d->url,
                    'original_name'  => $d->original_name,
                    'mime_type'      => $d->mime_type,
                    'is_image'       => $d->is_image,
                    'formatted_size' => $d->formatted_size,
                ]),
            ]),
            'promptPhotoId' => session()->pull('prompt_photo_id', false),
        ]);
    }

    public function availableUnits(Request $request)
    {
        $request->validate([
            'unit_type_id' => 'required|exists:unit_types,id',
            'check_in'     => 'required|date',
            'check_out'    => 'required|date|after:check_in',
        ]);

        $unitType = UnitType::findOrFail($request->unit_type_id);

        $bookedUnitIds = Booking::where('unit_type_id', $unitType->id)
            ->whereNotIn('status', ['cancelled'])
            ->where('check_in', '<', $request->check_out)
            ->where('check_out', '>', $request->check_in)
            ->pluck('unit_id');

        $units = $unitType->units()
            ->where('status', 'available')
            ->whereNotIn('id', $bookedUnitIds)
            ->get(['id', 'unit_number', 'floor'])
            ->map(fn($u) => [
                'id'          => $u->id,
                'unit_number' => $u->unit_number,
                'floor'       => $u->floor,
                'label'       => 'Unit ' . $u->unit_number . ($u->floor ? ' · ' . $u->floor . ' Floor' : ''),
            ]);

        return response()->json($units);
    }

    public function checkIn(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()->can('confirm-checkin'), 403);

        $user = auth()->user();
        if (!$user->hasGlobalAccess()) {
            abort_unless(
                in_array($booking->building_id, $user->accessibleBuildingIds() ?? []),
                403
            );
        }

        if (!$booking->canCheckIn()) {
            return back()->with('error', 'This booking cannot be checked in at this time.');
        }

        $validated = $request->validate([
            'amount_received'        => 'required|numeric|min:0',
            'checkin_payment_method' => 'required|in:pos,bank_transfer,paystack',
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

        AuditLog::log('booking.checked_in', $booking, ['status' => 'confirmed'], ['status' => 'checked_in', 'checked_in_by' => auth()->id()]);

        $recipients = NotificationService::getUsersByRoles(['manager', 'receptionist'], $booking->building_id);
        Notification::send($recipients, new GuestCheckedInNotification($booking));

        // Notify the guest
        try {
            Mail::to($booking->guest_email)->send(new GuestCheckedIn($booking));
        } catch (\Exception $e) {
            \Log::error('Failed to send guest check-in email', [
                'booking_reference' => $booking->booking_reference,
                'error'             => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Guest checked in successfully.');
    }

    public function checkOut(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()->can('confirm-checkin'), 403);

        $user = auth()->user();
        if (!$user->hasGlobalAccess()) {
            abort_unless(
                in_array($booking->building_id, $user->accessibleBuildingIds() ?? []),
                403
            );
        }

        if (!$booking->canCheckOut()) {
            return back()->with('error', 'This booking cannot be checked out at this time.');
        }

        $booking->update([
            'status'          => 'completed',
            'checked_out_at'  => now(),
            'checked_out_by'  => auth()->id(),
        ]);

        AuditLog::log('booking.checked_out', $booking,
            ['status' => 'checked_in'],
            ['status' => 'completed', 'checked_out_by' => auth()->id()]
        );

        try {
            Mail::to($booking->guest_email)->send(new GuestCheckedOut($booking));
        } catch (\Exception $e) {
            \Log::error('Failed to send guest checkout email', [
                'booking_reference' => $booking->booking_reference,
                'error'             => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Guest checked out successfully. Booking marked as completed.');
    }

    public function requestLateCheckout(Booking $booking)
    {
        if (!$booking->canRequestLateCheckout()) {
            return back()->with('error', 'Late checkout cannot be requested for this booking.');
        }

        $booking->update([
            'late_checkout_requested' => true,
            'late_checkout_status'    => 'pending',
            'late_checkout_fee'       => null, // set at approval time when hours are known
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
            'hours'  => 'required_if:action,approved|nullable|numeric|min:1|max:24',
        ]);

        $fee = 0;
        if ($validated['action'] === 'approved') {
            $booking->load('building');
            $ratePerHour = (float) ($booking->building->late_checkout_fee_per_hour ?? 10000);
            $fee         = $ratePerHour * (int) $validated['hours'];
        }

        $booking->update([
            'late_checkout_status'      => $validated['action'],
            'late_checkout_approved_by' => auth()->id(),
            'late_checkout_approved_at' => now(),
            'late_checkout_fee'         => $validated['action'] === 'approved' ? $fee : null,
            'total_amount'              => $validated['action'] === 'approved'
                ? $booking->total_amount + $fee
                : $booking->total_amount,
        ]);

        // Notify the receptionist/staff who requested it
        $booking->load('checkedInBy');
        if ($booking->checkedInBy && $booking->checkedInBy->id !== auth()->id()) {
            $booking->checkedInBy->notify(new LateCheckoutDecisionNotification($booking, $validated['action']));
        }

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
            'description'      => "Late checkout fee - {$booking->guest_name} ({$booking->booking_reference})",
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

    public function requestCautionRefund(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()->can('confirm-checkin'), 403);

        if ($booking->caution_fee <= 0) {
            return back()->with('error', 'This booking has no caution fee.');
        }
        if ($booking->caution_fee_refunded) {
            return back()->with('error', 'Caution fee already processed.');
        }
        if ($booking->caution_refund_requested) {
            return back()->with('error', 'A refund request is already pending approval.');
        }

        $validated = $request->validate([
            'action'           => 'required|in:full_refund,partial_deduction,full_forfeit',
            'reason'           => 'required_if:action,partial_deduction,full_forfeit|nullable|string|max:500',
            'deduction_amount' => 'required_if:action,partial_deduction|nullable|numeric|min:1',
        ]);

        if (
            $validated['action'] === 'partial_deduction' &&
            (float) $validated['deduction_amount'] >= (float) $booking->caution_fee
        ) {
            return back()->with('error', 'Deduction cannot equal or exceed the full caution fee. Use Full Forfeit instead.');
        }

        $booking->update([
            'caution_refund_requested'        => true,
            'caution_refund_requested_at'     => now(),
            'caution_refund_requested_by'     => auth()->id(),
            'caution_refund_action'           => $validated['action'],
            'caution_refund_reason'           => $validated['reason'] ?? null,
            'caution_refund_deduction_amount' => $validated['action'] === 'partial_deduction'
                ? $validated['deduction_amount'] : null,
        ]);

        AuditLog::log('booking.caution_refund_requested', $booking,
            ['caution_refund_requested' => false],
            ['action' => $validated['action'], 'by' => auth()->id()]
        );

        $recipients = NotificationService::getUsersByRoles(['manager', 'super-admin'], $booking->building_id);
        Notification::send($recipients, new CautionRefundRequestedNotification($booking));

        return back()->with('success', 'Caution refund request submitted. Manager will review it shortly.');
    }

    public function refundCautionFee(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()->can('manage-bookings'), 403);

        if ($booking->caution_fee <= 0) {
            return back()->with('error', 'This booking has no caution fee.');
        }
        if ($booking->caution_fee_refunded) {
            return back()->with('error', 'Caution fee already processed.');
        }

        // Use receptionist-submitted values if a request exists,
        // otherwise use values submitted directly by manager
        $action          = $booking->caution_refund_action
            ?? $request->validate(['action' => 'required|in:full_refund,partial_deduction,full_forfeit'])['action'];
        $deductionAmount = $booking->caution_refund_deduction_amount ?? $request->input('deduction_amount');
        $reason          = $booking->caution_refund_reason ?? $request->input('reason');

        $deduction = 0;

        switch ($action) {
            case 'full_refund':
                $deduction      = 0;
                $successMessage = 'Caution fee marked as fully refunded.';
                break;

            case 'partial_deduction':
                $deduction = (float) $deductionAmount;
                if ($deduction <= 0 || $deduction >= (float) $booking->caution_fee) {
                    return back()->with('error', 'Invalid deduction amount.');
                }
                $successMessage = '₦' . number_format($deduction, 0) . ' deducted. ₦' .
                    number_format($booking->caution_fee - $deduction, 0) . ' refunded.';
                break;

            case 'full_forfeit':
                $deduction      = (float) $booking->caution_fee;
                $successMessage = 'Caution fee fully forfeited and recorded as income.';
                break;

            default:
                return back()->with('error', 'Invalid action.');
        }

        $booking->update([
            'caution_fee_refunded'         => true,
            'caution_fee_refunded_at'      => now(),
            'caution_fee_refunded_by'      => auth()->id(),
            'caution_fee_deduction'        => $deduction > 0 ? $deduction : null,
            'caution_fee_deduction_reason' => $reason,
        ]);

        if ($deduction > 0) {
            FinancialTransaction::create([
                'building_id'      => $booking->building_id,
                'recorded_by'      => auth()->id(),
                'type'             => 'income',
                'category'         => 'caution_fee_deduction',
                'reference_type'   => Booking::class,
                'reference_id'     => $booking->id,
                'description'      => "Caution fee deduction — {$booking->guest_name} ({$booking->booking_reference})"
                    . ($reason ? ": {$reason}" : ''),
                'amount'           => $deduction,
                'payment_method'   => 'cash',
                'transaction_date' => now()->toDateString(),
            ]);
        }

        AuditLog::log('booking.caution_fee_processed', $booking,
            ['caution_fee_refunded' => false],
            ['action' => $action, 'deduction' => $deduction, 'by' => auth()->id()]
        );

        // Notify all receptionists in the building + admins
        $recipients = NotificationService::getUsersByRoles(['receptionist', 'super-admin'], $booking->building_id);
        Notification::send($recipients, new CautionRefundProcessedNotification($booking));

        return back()->with('success', $successMessage);
    }

}

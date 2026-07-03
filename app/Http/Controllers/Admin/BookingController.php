<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\CautionFeeCharge;
use App\Models\Unit;
use App\Notifications\BookingModifiedNotification;
use App\Notifications\CautionChargeNotification;
use App\Notifications\CautionRefundProcessedNotification;
use App\Notifications\CautionRefundRequestedNotification;
use App\Notifications\GuestCheckedOutNotification;
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

        $bookings = $query->orderBy($sortBy, $sortOrder)->paginate(10)->withQueryString();

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
                'unitTypes:id,building_id,name,bedroom_type,base_price_per_night,max_guests',
                'unitTypes.units:id,unit_type_id,unit_number,floor,status,is_available',
            ])
            ->select('id', 'name', 'caution_fee_amount', 'standard_checkout_time', 'late_checkout_fee_per_hour')
            ->get();

        return Inertia::render('Admin/Bookings/Create', [
            'buildings'  => $buildings,
            'prefill' => [
                'building_id'      => request('building_id'),
                'unit_type_id'     => request('unit_type_id'),
                'unit_id'          => request('unit_id'),
                'check_in'         => request('check_in'),
                'check_out'        => request('check_out'),
                'nights'           => request('nights'),
                'guests'           => request('guests'),
                'guest_name'       => request('guest_name'),
                'guest_email'      => request('guest_email'),
                'guest_phone'      => request('guest_phone'),
                'special_requests' => request('special_requests'),
            ],
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
                $availableUnit = Unit::findOrFail($validated['unit_id']);

                // Verify the unit belongs to the selected unit type
                if ($availableUnit->unit_type_id !== $unitType->id) {
                    return redirect()->back()
                        ->with('error', 'Selected unit does not belong to the chosen unit type.')
                        ->withInput();
                }

                // Verify it's actually available for the dates
                $conflict = Booking::where('unit_id', $availableUnit->id)
                    ->whereNotIn('status', ['cancelled', 'paused'])
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

            // Calculate pricing using the model method so nothing is missed
            $bookingModel = new Booking([
                'check_in'  => $validated['check_in'],
                'check_out' => $validated['check_out'],
            ]);
            $bookingModel->calculateTotal($unitType);

            // Create booking
            $booking = Booking::create([
                'booking_reference' => Booking::generateReference(),
                'building_id'       => $building->id,
                'unit_type_id'      => $unitType->id,
                'unit_id'           => $availableUnit->id,
                'user_id'           => null,
                'created_by_admin_id' => auth()->id(),
                'check_in'          => $validated['check_in'],
                'check_out'         => $validated['check_out'],
                'guests'            => $validated['guests'],
                'nights'            => $bookingModel->nights,
                'guest_name'        => $validated['guest_name'],
                'guest_email'       => $validated['guest_email'],
                'guest_phone'       => $validated['guest_phone'],
                'special_requests'  => $validated['special_requests'] ?? null,
                'subtotal'          => $bookingModel->subtotal,
                'total_amount'      => $bookingModel->total_amount,
                'discount_type'     => $bookingModel->discount_type,
                'discount_percent'  => $bookingModel->discount_percent,
                'discount_amount'   => $bookingModel->discount_amount,
                'caution_fee'       => $bookingModel->caution_fee,
                'status'            => 'confirmed',
                'payment_status'    => 'paid',
                'payment_method'    => $validated['payment_method'],
                'paystack_reference'=> $validated['payment_reference'],
                'paid_at'           => now(),
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

            $recipients = NotificationService::getUsersByRoles(['manager'], $booking->building_id)
                ->merge(\App\Models\User::role('super-admin')->where('is_active', true)->get())
                ->unique('id')
                ->reject(fn ($u) => $u->id === auth()->id());
            NotificationService::send($recipients, new NewBookingNotification($booking));

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

    public function downloadInvoice(Booking $booking)
    {
        $user = auth()->user();
        abort_unless($user->can('view-bookings'), 403);
        if (! $user->hasGlobalAccess()) {
            abort_unless(in_array($booking->building_id, $user->accessibleBuildingIds() ?? []), 403);
        }

        return \App\Services\InvoiceService::download($booking);
    }

    public function show(Booking $booking)
    {
        $user = auth()->user();
        abort_unless($user->can('view-bookings'), 403);
        if (! $user->hasGlobalAccess()) {
            abort_unless(in_array($booking->building_id, $user->accessibleBuildingIds() ?? []), 403);
        }

        $booking->load([
            'building', 'unitType', 'unit', 'user', 'adjustments',
            'checkedInBy', 'checkedOutBy', 'lateCheckoutApprovedBy',
            'messages.sender',
            'adjustments.appliedBy',
            'documents.uploadedBy',
            'cautionCharges.recordedBy',
            'cautionCharges.voidedBy',
        ]);

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => array_merge($booking->toArray(), [
                'checked_in_by_name'  => $booking->checkedInBy?->name,
                'checked_out_by_name' => $booking->checkedOutBy?->name,
                'caution_used'        => $booking->caution_used,
                'caution_available'   => $booking->caution_available,
                'caution_charges'     => $booking->cautionCharges->map(fn($c) => [
                    'id'             => $c->id,
                    'category'       => $c->category,
                    'category_label' => \App\Models\CautionFeeCharge::CATEGORIES[$c->category] ?? $c->category,
                    'description'    => $c->description,
                    'amount'         => $c->amount,
                    'recorded_by'    => $c->recordedBy?->name,
                    'created_at'     => $c->created_at,
                    'voided_at'      => $c->voided_at,
                    'voided_by'      => $c->voidedBy?->name,
                    'void_reason'    => $c->void_reason,
                ]),
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

    public function modifyBooking(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()->can('manage-bookings'), 403);

        $user = auth()->user();
        if (!$user->hasGlobalAccess()) {
            abort_unless(
                in_array($booking->building_id, $user->accessibleBuildingIds() ?? []),
                403
            );
        }

        if (in_array($booking->status, ['cancelled', 'completed'])) {
            return back()->with('error', 'Cannot modify a cancelled or completed booking.');
        }

        $validated = $request->validate([
            'check_in'    => 'sometimes|date',
            'check_out'   => 'sometimes|date|after:check_in',
            'unit_id'     => 'sometimes|nullable|exists:units,id',
            'guests'      => 'sometimes|integer|min:1',
            'guest_name'  => 'sometimes|string|max:255',
            'guest_email' => 'sometimes|email|max:255',
            'guest_phone' => 'sometimes|string|max:20',
            'special_requests' => 'sometimes|nullable|string|max:1000',
        ]);

        $old = [
            'check_in'    => $booking->check_in->toDateString(),
            'check_out'   => $booking->check_out->toDateString(),
            'nights'      => $booking->nights,
            'unit_id'     => $booking->unit_id,
            'unit_number' => $booking->unit?->unit_number,
            'guests'      => $booking->guests,
            'guest_name'  => $booking->guest_name,
            'guest_email' => $booking->guest_email,
            'guest_phone' => $booking->guest_phone,
        ];

        $checkIn  = $validated['check_in']  ?? $booking->check_in->toDateString();
        $checkOut = $validated['check_out'] ?? $booking->check_out->toDateString();
        $unitId   = array_key_exists('unit_id', $validated) ? $validated['unit_id'] : $booking->unit_id;

        // Validate unit availability if dates or unit changed
        $datesChanged = $checkIn !== $booking->check_in->toDateString()
            || $checkOut !== $booking->check_out->toDateString();
        $unitChanged  = $unitId !== $booking->unit_id;

        if ($datesChanged || $unitChanged) {
            $conflict = Booking::where('unit_id', $unitId)
                ->where('id', '!=', $booking->id)
                ->whereNotIn('status', ['cancelled', 'paused'])
                ->where('check_in', '<', $checkOut)
                ->where('check_out', '>', $checkIn)
                ->exists();

            if ($conflict) {
                return back()->with('error', 'The selected unit is not available for the chosen dates.');
            }
        }

        // Recalculate nights and total if dates changed
        $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));

        $updates = array_filter([
            'check_in'         => $checkIn,
            'check_out'        => $checkOut,
            'nights'           => $nights,
            'unit_id'          => $unitId,
            'guests'           => $validated['guests']      ?? $booking->guests,
            'guest_name'       => $validated['guest_name']  ?? $booking->guest_name,
            'guest_email'      => $validated['guest_email'] ?? $booking->guest_email,
            'guest_phone'      => $validated['guest_phone'] ?? $booking->guest_phone,
            'special_requests' => $validated['special_requests'] ?? $booking->special_requests,
        ], fn($v) => $v !== null);

        // Recalculate total if dates changed
        if ($datesChanged) {
            $unitType = $booking->unitType;
            $subtotal = $unitType->base_price_per_night * $nights;
            $discount = \App\Services\DiscountService::resolve($nights);
            $discountAmt = $discount['percent'] > 0
                ? round($subtotal * ($discount['percent'] / 100), 2)
                : 0;
            $cautionFee = $nights === 1
                ? (float) $unitType->base_price_per_night
                : (float) ($booking->building->caution_fee_amount ?? 70000);

            $updates['subtotal']         = $subtotal;
            $updates['discount_percent'] = $discount['percent'];
            $updates['discount_amount']  = $discountAmt;
            $updates['caution_fee']      = $cautionFee;
            $updates['total_amount']     = ($subtotal - $discountAmt) + $cautionFee;
        }

        $booking->update($updates);

        $new = [
            'check_in'    => $checkIn,
            'check_out'   => $checkOut,
            'nights'      => $nights,
            'unit_id'     => $unitId,
            'guests'      => $updates['guests'],
            'guest_name'  => $updates['guest_name'],
            'guest_email' => $updates['guest_email'],
            'guest_phone' => $updates['guest_phone'],
        ];

        AuditLog::log('booking.modified', $booking, $old, $new);

        // Build a human-readable change list and notify managers.
        $fmtDate  = fn($d) => \Carbon\Carbon::parse($d)->format('j M');
        $newUnitNumber = $unitChanged ? optional(Unit::find($unitId))->unit_number : $old['unit_number'];
        $changes = [];
        if ($datesChanged) {
            $changes[] = "Dates {$fmtDate($old['check_in'])}→{$fmtDate($old['check_out'])} to {$fmtDate($checkIn)}→{$fmtDate($checkOut)}";
        }
        if ($unitChanged) {
            $changes[] = 'Unit ' . ($old['unit_number'] ?? '—') . ' → ' . ($newUnitNumber ?? '—');
        }
        if ($new['guests']      !== $old['guests'])      $changes[] = "Guests {$old['guests']} → {$new['guests']}";
        if ($new['guest_name']  !== $old['guest_name'])  $changes[] = "Guest name updated";
        if ($new['guest_email'] !== $old['guest_email']) $changes[] = "Email updated";
        if ($new['guest_phone'] !== $old['guest_phone']) $changes[] = "Phone updated";

        if (! empty($changes)) {
            $recipients = NotificationService::getUsersByRoles(['manager', 'super-admin'], $booking->building_id);
            NotificationService::send($recipients, new BookingModifiedNotification($booking, $changes, $user->name));
        }

        return back()->with('success', 'Booking updated successfully.');
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
            ->whereNotIn('status', ['cancelled', 'paused'])
            ->where('check_in', '<', $request->check_out)
            ->where('check_out', '>', $request->check_in)
            ->when($request->exclude_booking, fn($q) => $q->where('id', '!=', $request->exclude_booking))
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
            'checkin_payment_method' => 'required|in:pos,bank_transfer,cash',
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

        // Record the check-in payment in the financial ledger if any amount was collected
        if ((float) $validated['amount_received'] > 0) {
            FinancialTransaction::create([
                'building_id'       => $booking->building_id,
                'recorded_by'       => auth()->id(),
                'type'              => 'income',
                'category'          => 'booking',
                'reference_type'    => Booking::class,
                'reference_id'      => $booking->id,
                'description'       => "Check-in payment: {$booking->booking_reference} - {$booking->guest_name}",
                'amount'            => $validated['amount_received'],
                'payment_method'    => $validated['checkin_payment_method'],
                'transaction_date'  => now()->toDateString(),
            ]);
        }

        AuditLog::log('booking.checked_in', $booking, ['status' => 'confirmed'], ['status' => 'checked_in', 'checked_in_by' => auth()->id()]);

        $recipients = NotificationService::getUsersByRoles(['manager', 'receptionist', 'super-admin'], $booking->building_id);
        NotificationService::send($recipients, new GuestCheckedInNotification($booking));

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

        // Notify building managers that the stay has closed and the unit is free.
        $recipients = NotificationService::getUsersByRoles(['manager', 'super-admin'], $booking->building_id);
        NotificationService::send($recipients, new GuestCheckedOutNotification($booking));

        return back()->with('success', 'Guest checked out successfully. Booking marked as completed.');
    }

    public function pauseBooking(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()->can('confirm-checkin'), 403);

        if (!$booking->canBePaused()) {
            return back()->with('error', 'Only checked-in bookings can be paused.');
        }

        $validated = $request->validate([
            'paused_departure' => 'required|date|after_or_equal:' . $booking->check_in->toDateString() . '|before:' . $booking->check_out->toDateString(),
        ]);

        $departure      = Carbon::parse($validated['paused_departure']);
        $remainingNights = $departure->diffInDays($booking->check_out);

        if ($remainingNights <= 0) {
            return back()->with('error', 'Departure date must be before the scheduled checkout date.');
        }

        $booking->update([
            'status'           => 'paused',
            'paused_at'        => now(),
            'paused_by'        => auth()->id(),
            'paused_departure' => $validated['paused_departure'],
            'remaining_nights' => $remainingNights,
        ]);

        AuditLog::log('booking.paused', $booking,
            ['status' => 'checked_in', 'check_out' => $booking->check_out->toDateString()],
            ['status' => 'paused', 'remaining_nights' => $remainingNights, 'paused_departure' => $validated['paused_departure']]
        );

        return back()->with('success', "Booking paused. {$remainingNights} night(s) remaining for the guest to use.");
    }

    public function resumeBooking(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()->can('confirm-checkin'), 403);

        if (!$booking->canBeResumed()) {
            return back()->with('error', 'Only paused bookings can be resumed.');
        }

        $validated = $request->validate([
            'resume_check_in' => 'required|date|after_or_equal:today',
            'unit_id'         => 'nullable|exists:units,id',
        ]);

        $newCheckIn  = Carbon::parse($validated['resume_check_in']);
        $newCheckOut = $newCheckIn->copy()->addDays($booking->remaining_nights);
        $unitId = !empty($validated['unit_id']) ? $validated['unit_id'] : $booking->unit_id;

        // Verify unit belongs to same unit type
        $unit = Unit::findOrFail($unitId);
        if ($unit->unit_type_id !== $booking->unit_type_id) {
            return back()->with('error', 'Selected unit must be of the same type as the original booking.');
        }

        // Check availability
        $conflict = Booking::where('unit_id', $unitId)
            ->where('id', '!=', $booking->id)
            ->whereNotIn('status', ['cancelled', 'paused'])
            ->where('check_in', '<', $newCheckOut->toDateString())
            ->where('check_out', '>', $newCheckIn->toDateString())
            ->exists();

        if ($conflict) {
            return back()->with('error', 'The selected unit is not available for the resumed dates.');
        }

        $booking->update([
            'status'           => 'confirmed',
            'check_in'         => $newCheckIn->toDateString(),
            'check_out'        => $newCheckOut->toDateString(),
            'nights'           => $booking->remaining_nights,
            'unit_id'          => $unitId,
            'resumed_at'       => now(),
            'resumed_by'       => auth()->id(),
            'remaining_nights' => null,
        ]);

        AuditLog::log('booking.resumed', $booking,
            ['status' => 'paused'],
            [
                'status'     => 'confirmed',
                'check_in'   => $newCheckIn->toDateString(),
                'check_out'  => $newCheckOut->toDateString(),
                'unit_id'    => $unitId,
                'resumed_by' => auth()->id(),
            ]
        );

        return back()->with('success', "Booking resumed. New stay: {$newCheckIn->format('M j')} → {$newCheckOut->format('M j, Y')}.");
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
        NotificationService::send($recipients, new LateCheckoutRequestedNotification($booking));

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
            'counts'   => Booking::scopedToUser($user)
                ->whereNotNull('late_checkout_status')
                ->selectRaw('late_checkout_status, COUNT(*) as count')
                ->groupBy('late_checkout_status')
                ->pluck('count', 'late_checkout_status')
                ->only(['pending', 'approved'])
                ->toArray(),
        ]);
    }

    public function requestCautionRefund(Request $request, Booking $booking)
    {
        $user = auth()->user();
        abort_unless($user->can('confirm-checkin'), 403);
        if (! $user->hasGlobalAccess()) {
            abort_unless(in_array($booking->building_id, $user->accessibleBuildingIds() ?? []), 403);
        }

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

        // Deductions here apply to the remaining balance (in-stay charges are already settled).
        if (
            $validated['action'] === 'partial_deduction' &&
            (float) $validated['deduction_amount'] >= (float) $booking->caution_available
        ) {
            return back()->with('error', 'Deduction cannot equal or exceed the remaining caution balance. Use Full Forfeit instead.');
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
        NotificationService::send($recipients, new CautionRefundRequestedNotification($booking));

        return back()->with('success', 'Caution refund request submitted. Manager will review it shortly.');
    }

    public function refundCautionFee(Request $request, Booking $booking)
    {
        $user = auth()->user();
        abort_unless($user->can('manage-bookings'), 403);
        if (! $user->hasGlobalAccess()) {
            abort_unless(in_array($booking->building_id, $user->accessibleBuildingIds() ?? []), 403);
        }

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

        // Amounts already drawn during the stay are settled/recorded via the charges ledger.
        // The values below apply to the remaining balance only.
        $alreadyUsed = $booking->caution_used;
        $available   = $booking->caution_available;
        $deduction   = 0; // extra amount deducted now (recorded as income now)

        switch ($action) {
            case 'full_refund':
                $deduction      = 0;
                $successMessage = $available > 0
                    ? '₦' . number_format($available, 0) . ' refunded to the guest.'
                    : 'Caution fee settled — nothing left to refund.';
                break;

            case 'partial_deduction':
                $deduction = (float) $deductionAmount;
                if ($deduction <= 0 || $deduction >= $available) {
                    return back()->with('error', 'Invalid deduction amount.');
                }
                $successMessage = '₦' . number_format($deduction, 0) . ' deducted. ₦' .
                    number_format($available - $deduction, 0) . ' refunded.';
                break;

            case 'full_forfeit':
                $deduction      = $available;
                $successMessage = 'Remaining caution balance fully forfeited and recorded as income.';
                break;

            default:
                return back()->with('error', 'Invalid action.');
        }

        $totalKept = $alreadyUsed + $deduction;

        $booking->update([
            'caution_fee_refunded'         => true,
            'caution_fee_refunded_at'      => now(),
            'caution_fee_refunded_by'      => auth()->id(),
            'caution_fee_deduction'        => $totalKept > 0 ? $totalKept : null,
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
        NotificationService::send($recipients, new CautionRefundProcessedNotification($booking));

        return back()->with('success', $successMessage);
    }

    // ── In-stay charges against the caution fee (food, damages, etc.) ──

    public function storeCautionCharge(Request $request, Booking $booking)
    {
        $user = auth()->user();
        abort_unless($user->can('manage-bookings'), 403);
        if (! $user->hasGlobalAccess()) {
            abort_unless(in_array($booking->building_id, $user->accessibleBuildingIds() ?? []), 403);
        }

        if ($booking->caution_fee <= 0) {
            return back()->with('error', 'This booking has no caution fee to charge against.');
        }
        if ($booking->caution_fee_refunded) {
            return back()->with('error', 'The caution fee has already been settled; no further charges can be added.');
        }

        $validated = $request->validate([
            'category'    => 'required|in:food,damage,other',
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0.01',
        ]);

        $available = $booking->caution_available;
        if ((float) $validated['amount'] > $available) {
            return back()->with('error',
                'Charge exceeds the remaining caution fee (₦' . number_format($available, 0) . ' available).');
        }

        $charge = null;
        \DB::transaction(function () use (&$charge, $booking, $validated, $user) {
            $txn = FinancialTransaction::create([
                'building_id'      => $booking->building_id,
                'recorded_by'      => $user->id,
                'type'             => 'income',
                'category'         => CautionFeeCharge::INCOME_CATEGORY[$validated['category']],
                'reference_type'   => Booking::class,
                'reference_id'     => $booking->id,
                'description'      => CautionFeeCharge::CATEGORIES[$validated['category']]
                    . " — {$booking->guest_name} ({$booking->booking_reference}): {$validated['description']}",
                'amount'           => $validated['amount'],
                'payment_method'   => 'caution_fee',
                'transaction_date' => now()->toDateString(),
            ]);

            $charge = $booking->cautionCharges()->create([
                'category'                 => $validated['category'],
                'description'              => $validated['description'],
                'amount'                   => $validated['amount'],
                'recorded_by'              => $user->id,
                'financial_transaction_id' => $txn->id,
            ]);
        });

        AuditLog::log('booking.caution_charge_added', $booking, [], [
            'category' => $charge->category,
            'amount'   => $charge->amount,
            'by'       => $user->id,
        ]);

        $recipients = NotificationService::getUsersByRoles(['manager', 'super-admin'], $booking->building_id);
        NotificationService::send($recipients, new CautionChargeNotification($booking, $charge, 'added'));

        return back()->with('success',
            '₦' . number_format((float) $validated['amount'], 0) . ' charged to the caution fee. ₦'
            . number_format($booking->fresh()->caution_available, 0) . ' remaining.');
    }

    public function voidCautionCharge(Request $request, Booking $booking, CautionFeeCharge $charge)
    {
        $user = auth()->user();
        abort_unless($user->can('manage-bookings'), 403);
        if (! $user->hasGlobalAccess()) {
            abort_unless(in_array($booking->building_id, $user->accessibleBuildingIds() ?? []), 403);
        }
        abort_unless($charge->booking_id === $booking->id, 404);

        if ($charge->isVoided()) {
            return back()->with('error', 'This charge has already been voided.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        \DB::transaction(function () use ($charge, $validated, $user) {
            // Reverse the recognised income.
            $charge->financialTransaction?->delete();

            $charge->update([
                'voided_at'                => now(),
                'voided_by'                => $user->id,
                'void_reason'              => $validated['reason'],
                'financial_transaction_id' => null,
            ]);
        });

        AuditLog::log('booking.caution_charge_voided', $booking, [], [
            'charge_id' => $charge->id,
            'amount'    => $charge->amount,
            'reason'    => $validated['reason'],
            'by'        => $user->id,
        ]);

        $recipients = NotificationService::getUsersByRoles(['manager', 'super-admin'], $booking->building_id);
        NotificationService::send($recipients, new CautionChargeNotification($booking, $charge, 'voided'));

        return back()->with('success', 'Charge voided and reversed.');
    }

}

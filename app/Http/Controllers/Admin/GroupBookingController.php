<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmation;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingGroup;
use App\Models\Building;
use App\Models\Unit;
use App\Models\UnitType;
use App\Notifications\NewBookingNotification;
use App\Services\NotificationService;
use App\Traits\ScopedByBuilding;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class GroupBookingController extends Controller
{
    use ScopedByBuilding;

    public function create()
    {
        abort_unless(auth()->user()->can('create-bookings'), 403);

        $buildings = $this->accessibleBuildings()
            ->with([
                'unitTypes:id,building_id,name,base_price_per_night,max_guests',
                'unitTypes.units:id,unit_type_id,unit_number,floor,status,is_available',
            ])
            ->select('id', 'name', 'caution_fee_amount', 'one_night_caution_uses_rate')
            ->get();

        return Inertia::render('Admin/Bookings/GroupCreate', ['buildings' => $buildings]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('create-bookings'), 403);

        $validated = $request->validate([
            'building_id'          => 'required|exists:buildings,id',
            'check_in'             => 'required|date',
            'check_out'            => 'required|date|after:check_in',
            'organization_id'      => 'nullable|exists:organizations,id',
            'lead_name'            => 'required|string|max:255',
            'lead_email'           => 'nullable|email|max:255',
            'lead_phone'           => 'nullable|string|max:30',
            'payment_method'       => 'required|in:pos,bank_transfer',
            'payment_reference'    => 'nullable|string|max:255',
            'discount_mode'        => 'required|in:auto,manual,none',
            'manual_discount'      => 'nullable|numeric|min:0|required_if:discount_mode,manual',
            'discount_reason'      => 'nullable|string|max:255|required_if:discount_mode,manual',
            // How much is collected at booking for the whole group: the full total
            // now, a deposit now (split across units), or nothing (before check-in).
            'payment_timing'       => 'nullable|in:full,deposit,later',
            'deposit_amount'       => 'nullable|numeric|min:1|required_if:payment_timing,deposit',
            'members'                    => 'required|array|min:2',
            'members.*.unit_id'          => 'required|distinct|exists:units,id',
            'members.*.guest_name'       => 'required|string|max:255',
            'members.*.guest_email'      => 'nullable|email|max:255',
            'members.*.guest_phone'      => 'nullable|string|max:30',
            'members.*.guests'           => 'required|integer|min:1',
        ]);

        $building = $this->accessibleBuildings()->findOrFail($validated['building_id']);

        $checkIn  = Carbon::parse($validated['check_in'])->startOfDay();
        $checkOut = Carbon::parse($validated['check_out'])->startOfDay();
        if ($checkIn->isBefore(now()->startOfDay())) {
            return back()->with('error', 'Check-in date cannot be in the past.')->withInput();
        }

        // Validate every unit up front (same building, available for the shared dates)
        $units = Unit::with('unitType')->whereIn('id', collect($validated['members'])->pluck('unit_id'))->get()->keyBy('id');
        foreach ($validated['members'] as $m) {
            $unit = $units->get($m['unit_id']);
            if (! $unit || (int) $unit->unitType?->building_id !== (int) $building->id) {
                return back()->with('error', 'A selected unit does not belong to this building.')->withInput();
            }
            $conflict = Booking::where('unit_id', $unit->id)
                ->whereNotIn('status', ['cancelled', 'paused'])
                ->where('check_in', '<', $checkOut->toDateString())
                ->where('check_out', '>', $checkIn->toDateString())
                ->exists();
            if ($conflict) {
                return back()->with('error', "Unit {$unit->unit_number} is not available for these dates.")->withInput();
            }
        }

        // A whole-group manual discount is a single ₦ figure spread across the
        // per-unit bookings in proportion to each unit's room subtotal, so the
        // per-unit financial records add up to exactly the discount entered.
        $nights   = (int) $checkIn->diffInDays($checkOut);
        $discountAlloc = [];
        if ($validated['discount_mode'] === 'manual') {
            $subtotals = [];
            foreach ($validated['members'] as $i => $m) {
                $subtotals[$i] = $nights * (float) $units->get($m['unit_id'])->unitType->base_price_per_night;
            }
            $totalSub    = array_sum($subtotals) ?: 1;
            $manualTotal = min((float) $validated['manual_discount'], array_sum($subtotals));
            $running     = 0.0;
            $lastKey     = array_key_last($subtotals);
            foreach ($subtotals as $i => $sub) {
                $discountAlloc[$i] = $i === $lastKey
                    ? round($manualTotal - $running, 2)                       // absorb rounding drift
                    : round($manualTotal * ($sub / $totalSub), 2);
                $running += $discountAlloc[$i];
            }
        }

        $group = null;
        DB::transaction(function () use (&$group, $validated, $building, $units, $checkIn, $checkOut, $discountAlloc) {
            $group = BookingGroup::create([
                'reference'       => BookingGroup::generateReference(),
                'building_id'     => $building->id,
                'organization_id' => $validated['organization_id'] ?? null,
                'lead_name'       => $validated['lead_name'],
                'lead_email'      => $validated['lead_email'] ?? null,
                'lead_phone'      => $validated['lead_phone'] ?? null,
                'created_by'      => auth()->id(),
            ]);

            $created = [];
            foreach ($validated['members'] as $i => $m) {
                $unit     = $units->get($m['unit_id']);
                $unitType = $unit->unitType;

                // Discount applies to the whole group: automatic multi-room rate,
                // no discount, or a manual figure allocated to this unit's share.
                $opts = match ($validated['discount_mode']) {
                    'manual' => [
                        'discount_mode'   => 'manual',
                        'manual_discount' => $discountAlloc[$i] ?? 0,
                        'discount_reason' => $validated['discount_reason'] ?? null,
                    ],
                    'none'   => ['discount_mode' => 'none'],
                    default  => ['unit_count' => count($validated['members'])],
                };

                $model = new Booking(['check_in' => $checkIn->toDateString(), 'check_out' => $checkOut->toDateString()]);
                $model->calculateTotal($unitType, $opts);

                $created[$i] = Booking::create([
                    'booking_reference'   => Booking::generateReference(),
                    'building_id'         => $building->id,
                    'unit_type_id'        => $unitType->id,
                    'unit_id'             => $unit->id,
                    'organization_id'     => $validated['organization_id'] ?? null,
                    'booking_group_id'    => $group->id,
                    'created_by_admin_id' => auth()->id(),
                    'check_in'            => $checkIn->toDateString(),
                    'check_out'           => $checkOut->toDateString(),
                    'guests'              => $m['guests'],
                    'nights'              => $model->nights,
                    'guest_name'          => $m['guest_name'],
                    'guest_email'         => $m['guest_email'] ?? $validated['lead_email'] ?? '',
                    'guest_phone'         => $m['guest_phone'] ?? $validated['lead_phone'] ?? '',
                    'subtotal'            => $model->subtotal,
                    'total_amount'        => $model->total_amount,
                    'currency'            => 'NGN',
                    'discount_type'       => $model->discount_type,
                    'discount_percent'    => $model->discount_percent,
                    'discount_amount'     => $model->discount_amount,
                    'discount_reason'     => $model->discount_reason,
                    'caution_fee'         => $model->caution_fee,
                    'policy_version'      => $building->currentPolicy?->version,
                    'status'              => 'confirmed',
                    // Payment is applied below per the chosen timing.
                    'payment_status'      => 'pending',
                    'amount_received'     => 0,
                    'payment_method'      => $validated['payment_method'],
                    'paystack_reference'  => $validated['payment_reference'] ?? null,
                    'paid_at'             => null,
                ]);
            }

            // Collect the whole-group amount now: the full total, a deposit split
            // across units in proportion to each unit's total, or nothing (the
            // balance is then due per unit before check-in).
            $timing     = $validated['payment_timing'] ?? 'full';
            $grandTotal = array_sum(array_map(fn ($b) => (float) $b->total_amount, $created));
            $paymentAlloc = [];
            if ($timing === 'deposit') {
                $depositTotal = min((float) ($validated['deposit_amount'] ?? 0), $grandTotal);
                $running = 0.0;
                $lastKey = array_key_last($created);
                foreach ($created as $i => $b) {
                    $paymentAlloc[$i] = $i === $lastKey
                        ? round($depositTotal - $running, 2)                                       // absorb rounding drift
                        : round($depositTotal * ((float) $b->total_amount / ($grandTotal ?: 1)), 2);
                    $running += $paymentAlloc[$i];
                }
            }

            foreach ($created as $i => $booking) {
                $amount = match ($timing) {
                    'deposit' => $paymentAlloc[$i] ?? 0,
                    'later'   => 0.0,
                    default   => (float) $booking->total_amount,
                };
                if ($amount > 0) {
                    $booking->recordPayment(
                        $amount,
                        $validated['payment_method'],
                        $validated['payment_reference'] ?? null,
                        "Group booking {$group->reference} · {$booking->booking_reference} - {$booking->guest_name}"
                    );
                }

                if ($booking->guest_email) {
                    try { Mail::to($booking->guest_email)->send(new BookingConfirmation($booking)); }
                    catch (\Exception $e) { \Log::error('Group booking email failed', ['ref' => $booking->booking_reference, 'error' => $e->getMessage()]); }
                }
            }
        });

        AuditLog::log('booking_group.created', $group, null, [
            'reference' => $group->reference,
            'units'     => count($validated['members']),
            'by'        => auth()->id(),
        ]);

        $recipients = NotificationService::getUsersByRoles(['manager', 'super-admin'], $building->id)
            ->reject(fn ($u) => $u->id === auth()->id());
        $group->loadMissing('bookings');
        if ($first = $group->bookings->first()) {
            NotificationService::send($recipients, new NewBookingNotification($first));
        }

        return redirect()->route('manage.booking-groups.show', $group->reference)
            ->with('success', "Group booking {$group->reference} created - " . count($validated['members']) . ' units.');
    }

    public function show(BookingGroup $group)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);

        $user = auth()->user();
        if (! $user->hasGlobalAccess()) {
            abort_unless(in_array($group->building_id, $user->accessibleBuildingIds() ?? []), 403);
        }

        $group->load(['building', 'organization', 'createdBy', 'bookings.unit', 'bookings.unitType']);

        return Inertia::render('Admin/Bookings/GroupShow', [
            'group'       => $group,
            'total'       => (float) $group->bookings->sum('total_amount'),
            'nights'      => $group->bookings->first()?->nights ?? 0,
        ]);
    }
}

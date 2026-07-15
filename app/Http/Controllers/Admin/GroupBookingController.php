<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmation;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingGroup;
use App\Models\Building;
use App\Models\FinancialTransaction;
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
            ->select('id', 'name', 'caution_fee_amount')
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

        $group = null;
        DB::transaction(function () use (&$group, $validated, $building, $units, $checkIn, $checkOut) {
            $group = BookingGroup::create([
                'reference'       => BookingGroup::generateReference(),
                'building_id'     => $building->id,
                'organization_id' => $validated['organization_id'] ?? null,
                'lead_name'       => $validated['lead_name'],
                'lead_email'      => $validated['lead_email'] ?? null,
                'lead_phone'      => $validated['lead_phone'] ?? null,
                'created_by'      => auth()->id(),
            ]);

            foreach ($validated['members'] as $m) {
                $unit     = $units->get($m['unit_id']);
                $unitType = $unit->unitType;

                $model = new Booking(['check_in' => $checkIn->toDateString(), 'check_out' => $checkOut->toDateString()]);
                $model->calculateTotal($unitType);

                $booking = Booking::create([
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
                    'caution_fee'         => $model->caution_fee,
                    'policy_version'      => $building->currentPolicy?->version,
                    'status'              => 'confirmed',
                    'payment_status'      => 'paid',
                    'payment_method'      => $validated['payment_method'],
                    'paystack_reference'  => $validated['payment_reference'] ?? null,
                    'paid_at'             => now(),
                ]);

                // One income transaction per unit, tagged with the group reference
                FinancialTransaction::create([
                    'building_id'      => $booking->building_id,
                    'recorded_by'      => auth()->id(),
                    'type'             => 'income',
                    'category'         => 'booking',
                    'reference_type'   => Booking::class,
                    'reference_id'     => $booking->id,
                    'description'      => "Group booking {$group->reference} · {$booking->booking_reference} - {$booking->guest_name}",
                    'amount'           => $booking->total_amount,
                    'payment_method'   => $validated['payment_method'],
                    'payment_reference'=> $validated['payment_reference'] ?? null,
                    'transaction_date' => now()->toDateString(),
                ]);

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
            ->with('success', "Group booking {$group->reference} created — " . count($validated['members']) . ' units.');
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

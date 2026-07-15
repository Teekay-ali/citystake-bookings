<?php

namespace App\Models;

use App\Services\DiscountService;
use App\Traits\HasBuildingScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory, SoftDeletes, HasBuildingScope;

    protected $appends = ['display_status'];

    protected $fillable = [
        'booking_reference',
        'building_id',
        'unit_type_id',
        'unit_id',
        'user_id',
        'created_by_admin_id',
        'check_in',
        'check_out',
        'nights',
        'guests',
        'subtotal',
        'total_amount',
        'currency',
        'price_usd',
        'exchange_rate',
        'discount_type',
        'discount_percent',
        'discount_amount',
        'discount_reason',
        'status',
        'payment_status',
        'payment_method',
        'paystack_reference',
        'monnify_reference',
        'payment_reference',
        'paid_at',
        'special_requests',
        'policy_version',
        'cancellation_reason',
        'cancelled_at',
        'checked_in_at',
        'checked_in_by',
        'checked_out_at',
        'checked_out_by',
        'amount_received',
        'checkin_payment_method',
        'checkin_notes',
        'guest_name',
        'guest_email',
        'guest_phone',
        'late_checkout_requested',
        'late_checkout_status',
        'late_checkout_fee',
        'late_checkout_approved_by',
        'late_checkout_approved_at',
        'late_checkout_settled_at',
        'caution_fee',
        'caution_fee_deduction',
        'caution_fee_deduction_reason',
        'caution_fee_refunded',
        'caution_fee_refunded_at',
        'caution_fee_refunded_by',
        'caution_refund_requested',
        'caution_refund_requested_at',
        'caution_refund_requested_by',
        'caution_refund_action',
        'caution_refund_reason',
        'caution_refund_deduction_amount',
        'paused_at',
        'paused_by',
        'paused_departure',
        'remaining_nights',
        'resumed_at',
        'resumed_by',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'amount_received' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'late_checkout_requested'   => 'boolean',
        'late_checkout_fee'         => 'decimal:2',
        'late_checkout_approved_at' => 'datetime',
        'late_checkout_settled_at'  => 'datetime',
        'caution_fee'              => 'decimal:2',
        'caution_fee_deduction'    => 'decimal:2',
        'caution_fee_refunded'     => 'boolean',
        'caution_fee_refunded_at'  => 'datetime',
        'caution_refund_requested'        => 'boolean',
        'caution_refund_requested_at'     => 'datetime',
        'caution_refund_deduction_amount' => 'decimal:2',
        'paused_at'        => 'datetime',
        'paused_departure' => 'date',
        'resumed_at'       => 'datetime',
    ];

    public function scopeCheckedIn($query)
    {
        return $query->where('status', 'checked_in');
    }

    // Relationships
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function createdByAdmin()
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BookingMessage::class)->orderBy('created_at');
    }

    public function adjustments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BookingAdjustment::class);
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Document::class, 'documentable')->orderBy('sort_order');
    }

    public function cautionCharges(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CautionFeeCharge::class)->latest();
    }

    // Total non-voided charges drawn against the caution fee during the stay.
    public function getCautionUsedAttribute(): float
    {
        $charges = $this->relationLoaded('cautionCharges')
            ? $this->cautionCharges->whereNull('voided_at')
            : $this->cautionCharges()->active()->get();

        return (float) $charges->sum('amount');
    }

    // Remaining caution fee available to draw against.
    public function getCautionAvailableAttribute(): float
    {
        return max(0, (float) $this->caution_fee - $this->caution_used);
    }

    // Expose the booking via its non-sequential reference in URLs (not the PK id).
    public function getRouteKeyName(): string
    {
        return 'booking_reference';
    }

    // Resolve by reference; fall back to a numeric id so legacy links never break.
    public function resolveRouteBinding($value, $field = null)
    {
        $query = $this->where('booking_reference', $value);
        if (is_numeric($value)) {
            $query->orWhere('id', $value);
        }
        return $query->firstOrFail();
    }

    // Helper methods
    public static function generateReference(): string
    {
        return 'CS-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    /**
     * Price the booking off $unitType (the billed/rate type - may differ from the
     * physically assigned unit's type when cross-graded).
     *
     * $opts:
     *   discount_mode: 'auto' (default) | 'manual' | 'none'
     *   manual_discount: float ₦ amount (used when mode = 'manual')
     *   discount_reason: string
     */
    public function calculateTotal(UnitType $unitType, array $opts = []): void
    {
        $this->nights = Carbon::parse($this->check_in)->diffInDays($this->check_out);

        $building = $unitType->building ?? $unitType->building()->first();
        $defaultCautionFee = (float) ($building->caution_fee_amount ?? 70000);

        $currency = strtoupper($opts['currency'] ?? 'NGN');

        if ($currency === 'USD') {
            // Contracted in USD; converted to NGN at the rate locked now.
            // All financials are stored/reported in NGN. Caution stays NGN.
            $this->currency      = 'USD';
            $this->price_usd     = (float) ($opts['price_usd'] ?? 0);
            $this->exchange_rate = (float) ($opts['exchange_rate'] ?? 0);
            $this->subtotal      = round($this->price_usd * $this->exchange_rate, 2);
            $this->caution_fee   = $defaultCautionFee;
            // Nightly auto-discount is meaningless for a flat USD contract
            $mode = ($opts['discount_mode'] ?? 'none') === 'manual' ? 'manual' : 'none';
        } else {
            $this->currency      = 'NGN';
            $this->price_usd     = null;
            $this->exchange_rate = null;
            $this->subtotal      = $this->nights * $unitType->base_price_per_night;
            $this->caution_fee   = (int) $this->nights === 1
                ? (float) $unitType->base_price_per_night
                : $defaultCautionFee;
            $mode = $opts['discount_mode'] ?? 'auto';
        }

        if ($mode === 'manual') {
            // Discretionary flat ₦ discount - never exceeds the subtotal.
            $amount = min((float) ($opts['manual_discount'] ?? 0), (float) $this->subtotal);
            $this->discount_type    = $amount > 0 ? 'manual' : null;
            $this->discount_percent = 0;
            $this->discount_amount  = $amount;
            $this->discount_reason  = $amount > 0 ? ($opts['discount_reason'] ?? null) : null;
        } elseif ($mode === 'none') {
            $this->discount_type    = null;
            $this->discount_percent = 0;
            $this->discount_amount  = 0;
            $this->discount_reason  = null;
        } else {
            // Automatic rule-based discount
            $discount = DiscountService::resolve((int) $this->nights);
            $this->discount_type    = $discount['type'];
            $this->discount_percent = $discount['percent'];
            $this->discount_amount  = $discount['percent'] > 0
                ? round($this->subtotal * ($discount['percent'] / 100), 2)
                : 0;
            $this->discount_reason  = null;
        }

        // Total includes caution fee
        $this->total_amount = ($this->subtotal - $this->discount_amount)
            + $this->caution_fee;
    }

    // True when the physically assigned unit is of a different type than the billed type.
    public function getCrossGradedAttribute(): bool
    {
        return $this->unit_id
            && $this->unit
            && (int) $this->unit->unit_type_id !== (int) $this->unit_type_id;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function canCheckOut(): bool
    {
        return $this->status === 'checked_in';
    }

    public function canBePaused(): bool
    {
        return $this->status === 'checked_in';
    }

    public function canBeResumed(): bool
    {
        return $this->status === 'paused';
    }

    public function canBeCancelled(): bool
    {
        return $this->status !== 'cancelled'
            && $this->status !== 'completed'
            && $this->status !== 'checked_in'
            && $this->status !== 'paused'
            && $this->check_in > now();
    }

    public function canCheckIn(): bool
    {
        return $this->status === 'confirmed'
            && $this->payment_status === 'paid'
            && $this->check_in->lte(now()->endOfDay());
    }

    public function lateCheckoutApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'late_checkout_approved_by');
    }

    public function canRequestLateCheckout(): bool
    {
        return in_array($this->status, ['confirmed', 'checked_in'])
            && !$this->late_checkout_requested;
    }

    public function canApproveLateCheckout(): bool
    {
        return $this->late_checkout_requested
            && $this->late_checkout_status === 'pending';
    }

    public function canSettleLateCheckout(): bool
    {
        return $this->late_checkout_status === 'approved'
            && !$this->late_checkout_settled_at;
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('check_in', '>=', now())
            ->where('status', '!=', 'cancelled');
    }

    public function scopePast($query)
    {
        return $query->where('check_out', '<', now());
    }

    public function scopeActive($query)
    {
        return $query->where('check_in', '<=', now())
            ->where('check_out', '>=', now())
            ->where('status', 'confirmed');
    }

    /**
     * Derived display status that accounts for active stays and completions
     * based on dates, but only for confirmed/checked_in bookings.
     * Cancelled and pending always take precedence.
     */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'cancelled') return 'cancelled';
        if ($this->status === 'completed') return 'completed';
        if ($this->status === 'checked_in') return 'checked_in';
        if ($this->status === 'paused') return 'paused';
        if ($this->payment_status === 'pending') return 'payment_pending';

        $today = now()->startOfDay();

        // Confirmed but checkout date has passed without manual checkout - flag it
        if ($this->check_out->lt($today)) return 'overdue_checkout';
        if ($this->check_in->lte($today) && $this->check_out->gte($today)) return 'active';

        return 'confirmed';
    }

}

<?php

namespace App\Models;

use App\Traits\HasBuildingScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'cleaning_fee',
        'service_charge',
        'total_amount',
        'discount_type',
        'discount_percent',
        'discount_amount',
        'status',
        'payment_status',
        'payment_method',
        'paystack_reference',
        'payment_reference',
        'paid_at',
        'special_requests',
        'cancellation_reason',
        'cancelled_at',
        'checked_in_at',
        'checked_in_by',
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
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'amount_received' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'late_checkout_requested'   => 'boolean',
        'late_checkout_fee'         => 'decimal:2',
        'late_checkout_approved_at' => 'datetime',
        'late_checkout_settled_at'  => 'datetime',
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

    // Helper methods
    public static function generateReference(): string
    {
        return 'CS-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    public function calculateTotal(UnitType $unitType, int $unitCount = 1): void
    {
        $this->nights         = Carbon::parse($this->check_in)->diffInDays($this->check_out);
        $this->subtotal       = $this->nights * $unitType->base_price_per_night;
        $this->cleaning_fee   = $unitType->cleaning_fee;
        $this->service_charge = $this->subtotal * ($unitType->service_charge_percent / 100);

        // Resolve discount
        $discount = \App\Services\DiscountService::resolve($this->nights, $unitCount);
        $this->discount_type    = $discount['type'];
        $this->discount_percent = $discount['percent'];
        $this->discount_amount  = $discount['percent'] > 0
            ? round($this->subtotal * ($discount['percent'] / 100), 2)
            : 0;

        // Discount applied to subtotal only (not cleaning fee or service charge)
        $this->total_amount = ($this->subtotal - $this->discount_amount)
            + $this->cleaning_fee
            + $this->service_charge;
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

    public function canBeCancelled(): bool
    {
        return $this->status !== 'cancelled'
            && $this->status !== 'completed'
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
        if ($this->status === 'checked_in') return 'checked_in';
        if ($this->payment_status === 'pending') return 'payment_pending';

        $today = now()->startOfDay();

        if ($this->check_out->lt($today)) return 'completed';
        if ($this->check_in->lte($today) && $this->check_out->gte($today)) return 'active';

        return 'confirmed'; // upcoming
    }

}

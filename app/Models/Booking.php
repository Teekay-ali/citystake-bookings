<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

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
        'status',
        'payment_status',
        'payment_method',
        'paystack_reference',
        'payment_reference',
        'paid_at',
        'special_requests',
        'cancellation_reason',
        'cancelled_at',
        'guest_name',
        'guest_email',
        'guest_phone',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
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

    public function calculateTotal(UnitType $unitType): void
    {
        $this->nights = Carbon::parse($this->check_in)->diffInDays($this->check_out);
        $this->subtotal = $this->nights * $unitType->base_price_per_night;
        $this->cleaning_fee = $unitType->cleaning_fee;
        $this->service_charge = $this->subtotal * ($unitType->service_charge_percent / 100);
        $this->total_amount = $this->subtotal + $this->cleaning_fee + $this->service_charge;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function canBeCancelled(): bool
    {
        return $this->status !== 'cancelled'
            && $this->status !== 'completed'
            && $this->check_in > now();
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
}

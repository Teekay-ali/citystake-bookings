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
        'booking_reference', 'property_id', 'user_id',
        'check_in', 'check_out', 'nights', 'guests',
        'subtotal', 'cleaning_fee', 'service_charge', 'total_amount',
        'status', 'payment_status', 'paystack_reference',
        'paid_at', 'special_requests', 'cancellation_reason'
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateReference(): string
    {
        return 'CS-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    public function calculateTotal(Property $property): void
    {
        $this->nights = Carbon::parse($this->check_in)->diffInDays($this->check_out);
        $this->subtotal = $this->nights * $property->base_price_per_night;
        $this->cleaning_fee = $property->cleaning_fee;
        $this->service_charge = $this->subtotal * ($property->service_charge_percent / 100);
        $this->total_amount = $this->subtotal + $this->cleaning_fee + $this->service_charge;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAdjustment extends Model
{
    protected $fillable = [
        'booking_id', 'applied_by', 'amount_type', 'amount_value',
        'amount_naira', 'reason', 'notes', 'payment_reference', 'transaction_date',
    ];

    protected $casts = [
        'amount_value'    => 'decimal:2',
        'amount_naira'    => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }
}

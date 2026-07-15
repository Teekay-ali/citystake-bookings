<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingInstallment extends Model
{
    protected $fillable = [
        'booking_id', 'week_number', 'due_date', 'amount',
        'paid_at', 'recorded_by', 'financial_transaction_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount'   => 'decimal:2',
        'paid_at'  => 'datetime',
    ];

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    // Due (its week has started) but not yet paid.
    public function isOverdue(): bool
    {
        return $this->paid_at === null && $this->due_date->isPast();
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

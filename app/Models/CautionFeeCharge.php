<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CautionFeeCharge extends Model
{
    protected $fillable = [
        'booking_id', 'category', 'description', 'amount',
        'recorded_by', 'financial_transaction_id',
        'voided_at', 'voided_by', 'void_reason',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public const CATEGORIES = [
        'food'   => 'Food / Restaurant',
        'damage' => 'Damaged Item',
        'other'  => 'Other',
    ];

    // Maps a charge category to the financial-transaction income category.
    public const INCOME_CATEGORY = [
        'food'   => 'restaurant',
        'damage' => 'caution_fee_deduction',
        'other'  => 'manual_income',
    ];

    public function scopeActive($query)
    {
        return $query->whereNull('voided_at');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id', 'recorded_by', 'type', 'category',
        'reference_type', 'reference_id',
        'description', 'amount',
        'payment_method', 'payment_reference', 'bank_name',
        'transaction_date', 'notes',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public static function categoryLabels(): array
    {
        return [
            'booking'        => 'Booking Payment',
            'late_checkout'  => 'Late Checkout Fee',
            'maintenance'    => 'Maintenance',
            'procurement'    => 'Procurement',
            'manual_income'  => 'Manual Income',
            'manual_expense' => 'Manual Expense',
        ];
    }
}

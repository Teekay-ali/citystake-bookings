<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One checkout→ready cycle for a unit. Owns the readiness status and the
 * hand-off timestamps (checkout → cleaned → inspected → ready) shared between
 * front desk and QC. A unit runs many turnovers over its life; the "active" one
 * is whichever isn't yet ready/blocked/cancelled.
 */
class UnitTurnover extends Model
{
    protected $fillable = [
        'unit_id', 'building_id', 'booking_id', 'status',
        'checked_out_at', 'cleaning_requested_at', 'cleaning_requested_by',
        'cleaning_completed_at', 'cleaning_completed_by',
        'qa_started_at', 'qa_completed_at', 'ready_at',
        'unit_inspection_id', 'blocked_date_id', 'notes',
    ];

    protected $casts = [
        'checked_out_at'        => 'datetime',
        'cleaning_requested_at' => 'datetime',
        'cleaning_completed_at' => 'datetime',
        'qa_started_at'         => 'datetime',
        'qa_completed_at'       => 'datetime',
        'ready_at'              => 'datetime',
    ];

    // Statuses that mean the turnover is still in flight.
    public const ACTIVE_STATUSES = ['cleaning_in_progress', 'cleaning_completed', 'qa_in_progress'];

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function unit(): BelongsTo         { return $this->belongsTo(Unit::class); }
    public function building(): BelongsTo     { return $this->belongsTo(Building::class); }
    public function booking(): BelongsTo      { return $this->belongsTo(Booking::class); }
    public function inspection(): BelongsTo   { return $this->belongsTo(UnitInspection::class, 'unit_inspection_id'); }
    public function blockedDate(): BelongsTo  { return $this->belongsTo(BlockedDate::class, 'blocked_date_id'); }
    public function requestedBy(): BelongsTo  { return $this->belongsTo(User::class, 'cleaning_requested_by'); }
    public function completedBy(): BelongsTo  { return $this->belongsTo(User::class, 'cleaning_completed_by'); }

    /**
     * The turnaround spans in minutes (null while a stage is unreached) — the
     * raw material for the QC turnaround-time analytics.
     */
    public function durations(): array
    {
        $mins = fn ($a, $b) => ($a && $b) ? $a->diffInMinutes($b) : null;

        return [
            'checkout_to_cleaned' => $mins($this->checked_out_at ?? $this->cleaning_requested_at, $this->cleaning_completed_at),
            'cleaned_to_qa'       => $mins($this->cleaning_completed_at, $this->qa_started_at),
            'qa_to_ready'         => $mins($this->qa_started_at, $this->ready_at),
            'total'               => $mins($this->checked_out_at ?? $this->cleaning_requested_at, $this->ready_at),
        ];
    }
}

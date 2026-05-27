<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EmergencyFundRequest extends Model
{
    protected $fillable = [
        'building_id',
        'requested_by',
        'approved_by',
        'amount',
        'reason',
        'urgency_description',
        'evidence',
        'status',
        'manager_approved_by',
        'manager_comment',
        'manager_approved_at',
        'ceo_comment',
        'payment_reference',
        'month_year',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'manager_approved_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at'     => 'datetime',
    ];

    protected $appends = ['evidence_url'];

    // ── Relationships ─────────────────────────────────────────

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function managerApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    // ── Accessors ─────────────────────────────────────────────

    public function getEvidenceUrlAttribute(): ?string
    {
        if (!$this->evidence) return null;
        if (str_starts_with($this->evidence, 'http')) return $this->evidence;
        return Storage::disk('public')->url($this->evidence);
    }

    // ── Helpers ───────────────────────────────────────────────

    public function isPending(): bool          { return $this->status === 'pending'; }
    public function isManagerApproved(): bool  { return $this->status === 'manager_approved'; }
    public function isApproved(): bool         { return $this->status === 'approved'; }
    public function isDeclined(): bool         { return $this->status === 'declined'; }
    public function isPaid(): bool             { return $this->status === 'paid'; }

    // ── Static helpers ────────────────────────────────────────

    public static function usedThisMonth(int $buildingId, ?string $monthYear = null): float
    {
        return static::where('building_id', $buildingId)
            ->whereIn('status', ['approved', 'paid'])
            ->where('month_year', $monthYear ?? now()->format('Y-m'))
            ->sum('amount');
    }

    public static function remainingThisMonth(int $buildingId, float $limit, ?string $monthYear = null): float
    {
        return max(0, $limit - static::usedThisMonth($buildingId, $monthYear));
    }
}

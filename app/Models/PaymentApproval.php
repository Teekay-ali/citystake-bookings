<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PaymentApproval extends Model
{
    protected $fillable = [
        'building_id',
        'requested_by',
        'approved_by',
        'type',
        'custom_type',
        'recipient_name',
        'amount',
        'description',
        'status',
        'ceo_comment',
        'payment_reference',
        'payment_evidence',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at'     => 'datetime',
    ];

    protected $appends = ['payment_evidence_url', 'type_label'];

    // ── Relationships ─────────────────────────────────────────────────────────

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

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getPaymentEvidenceUrlAttribute(): ?string
    {
        if (!$this->payment_evidence) return null;
        if (str_starts_with($this->payment_evidence, 'http')) return $this->payment_evidence;
        return Storage::disk('public')->url($this->payment_evidence);
    }

    public function getTypeLabelAttribute(): string
    {
        if ($this->type === 'miscellaneous' && $this->custom_type) {
            return $this->custom_type;
        }

        return match($this->type) {
            'salary'         => 'Salary',
            'bonus'          => 'Bonus',
            'vendor_payment' => 'Vendor Payment',
            'utility'        => 'Utility Bill',
            'maintenance'    => 'Maintenance',
            'miscellaneous'  => 'Miscellaneous',
            default          => ucfirst($this->type),
        };
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isDeclined(): bool { return $this->status === 'declined'; }
    public function isPaid(): bool     { return $this->status === 'paid'; }
}

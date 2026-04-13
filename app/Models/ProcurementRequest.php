<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'building_id', 'submitted_by',
        'vendor_id', 'supplier_name', 'supplier_phone', 'supplier_email',
        'accountant_approved_by', 'accountant_approved_at',
        'ceo_approved_by', 'ceo_approved_at',
        'purchased_by', 'purchased_at',
        'receipt_confirmed_by', 'receipt_confirmed_at',
        'title', 'justification', 'total_amount', 'notes',
        'status', 'rejection_reason', 'rejected_by_role',
    ];

    protected $casts = [
        'total_amount'           => 'decimal:2',
        'accountant_approved_at' => 'datetime',
        'ceo_approved_at'        => 'datetime',
        'purchased_at'           => 'datetime',
        'receipt_confirmed_at'   => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function accountantApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountant_approved_by');
    }

    public function ceoApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ceo_approved_by');
    }

    public function purchasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function receiptConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receipt_confirmed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementItem::class);
    }

    // ─── Workflow helpers ───────────────────────────────

    public static function generateReference(): string
    {
        return 'PR-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }

    public function canAccountantApprove(): bool
    {
        return $this->status === 'pending';
    }

    public function canCeoApprove(): bool
    {
        return $this->status === 'accountant_approved';
    }

    public function canMarkPurchased(): bool
    {
        return $this->status === 'ceo_approved';
    }

    public function canConfirmReceipt(): bool
    {
        return $this->status === 'purchased';
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'pending'             => 'Awaiting Accountant',
            'accountant_approved' => 'Awaiting CEO',
            'ceo_approved'        => 'Awaiting Purchase',
            'purchased'           => 'Awaiting Receipt',
            'completed'           => 'Completed',
            'rejected'            => 'Rejected',
            default               => ucfirst($this->status),
        };
    }
}

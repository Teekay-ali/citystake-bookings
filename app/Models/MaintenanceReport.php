<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id', 'vendor_id', 'submitted_by',
        'manager_approved_by', 'manager_approved_at',
        'accountant_approved_by', 'accountant_approved_at',
        'ceo_approved_by', 'ceo_approved_at',
        'payment_made_by', 'payment_made_at',
        'title', 'issue_type', 'description', 'location',
        'estimated_cost', 'actual_cost', 'repair_timeline',
        'photos', 'notes', 'status',
        'rejection_reason', 'rejected_by_role',
    ];

    protected $casts = [
        'photos'               => 'array',
        'manager_approved_at'  => 'datetime',
        'accountant_approved_at' => 'datetime',
        'ceo_approved_at'      => 'datetime',
        'payment_made_at'      => 'datetime',
        'repair_timeline'      => 'date',
        'estimated_cost'       => 'decimal:2',
        'actual_cost'          => 'decimal:2',
    ];

    // ─── Relationships ──────────────────────────────────────────

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

    public function managerApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    public function accountantApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountant_approved_by');
    }

    public function ceoApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ceo_approved_by');
    }

    public function paymentMadeBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_made_by');
    }

    // ─── Workflow helpers ───────────────────────────────────────

    public static function issueTypes(): array
    {
        return [
            'leakage'    => 'Leakage',
            'electrical' => 'Electrical',
            'ac_hvac'    => 'AC / HVAC',
            'appliance'  => 'Appliance',
            'plumbing'   => 'Plumbing',
            'structural' => 'Structural',
            'carpentry'  => 'Carpentry',
            'painting'   => 'Painting',
            'other'      => 'Other',
        ];
    }

    public function canManagerApprove(): bool
    {
        return $this->status === 'pending';
    }

    public function canAccountantApprove(): bool
    {
        return $this->status === 'manager_approved';
    }

    public function canCeoApprove(): bool
    {
        return $this->status === 'accountant_approved';
    }

    public function canMakePayment(): bool
    {
        return $this->status === 'ceo_approved';
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'pending'              => 'Awaiting Manager',
            'manager_approved'     => 'Awaiting Accountant',
            'accountant_approved'  => 'Awaiting CEO',
            'ceo_approved'         => 'Awaiting Payment',
            'completed'            => 'Completed',
            'rejected'             => 'Rejected',
            default                => ucfirst($this->status),
        };
    }
}

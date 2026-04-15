<?php

namespace App\Models;

use App\Traits\HasBuildingScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasFactory, SoftDeletes, HasBuildingScope;

    protected $fillable = [
        'building_id',
        'submitted_by',
        'resolved_by',
        'title',
        'description',
        'location',
        'severity',
        'status',
        'resolution_notes',
        'resolved_at',
        'photos',
    ];

    protected $casts = [
        'photos'      => 'array',
        'resolved_at' => 'datetime',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A round is one property's inspection sweep on a single day. It owns the
 * per-unit inspections and is the thing that closes (notifying the CEO once),
 * so an individual unit being finished never implies the day is done.
 */
class InspectionRound extends Model
{
    protected $fillable = [
        'building_id', 'round_date', 'status',
        'started_by', 'completed_by', 'completed_at', 'note',
    ];

    protected $casts = [
        'round_date'   => 'date',
        'completed_at' => 'datetime',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function unitInspections(): HasMany
    {
        return $this->hasMany(UnitInspection::class);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A property-level checklist section (common or outdoor) inspected once per
 * round — the counterpart to UnitInspection for spaces that aren't tied to a
 * single unit.
 */
class RoundSectionInspection extends Model
{
    protected $fillable = [
        'inspection_round_id', 'building_id', 'section',
        'inspector_id', 'status', 'result', 'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(InspectionRound::class, 'inspection_round_id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function itemResults(): MorphMany
    {
        return $this->morphMany(InspectionItemResult::class, 'inspectable');
    }
}

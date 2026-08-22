<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * One QC answer to a checklist item (pass / fail / n-a) with its note and photo
 * evidence. Polymorphic: belongs to either a UnitInspection or a
 * RoundSectionInspection.
 */
class InspectionItemResult extends Model
{
    protected $fillable = [
        'inspectable_type', 'inspectable_id',
        'item_key', 'item_label', 'section', 'category', 'bedroom_index',
        'result', 'note', 'photos', 'maintenance_report_id',
    ];

    protected $casts = [
        'photos'        => 'array',
        'bedroom_index' => 'integer',
    ];

    protected $appends = ['photo_urls'];

    public function inspectable(): MorphTo
    {
        return $this->morphTo();
    }

    public function maintenanceReport(): BelongsTo
    {
        return $this->belongsTo(MaintenanceReport::class);
    }

    public function getPhotoUrlsAttribute(): array
    {
        return collect($this->photos ?? [])
            ->map(fn ($p) => Storage::url($p))
            ->values()
            ->all();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InspectionFinding extends Model
{
    protected $fillable = [
        'unit_inspection_id', 'category', 'severity',
        'description', 'photos', 'resolved', 'maintenance_report_id',
    ];

    protected $casts = [
        'photos'   => 'array',
        'resolved' => 'boolean',
    ];

    protected $appends = ['photo_urls'];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(UnitInspection::class, 'unit_inspection_id');
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

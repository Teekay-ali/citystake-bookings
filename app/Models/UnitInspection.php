<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class UnitInspection extends Model
{
    protected $fillable = [
        'building_id', 'inspection_round_id', 'unit_id', 'inspector_id', 'created_by',
        'status', 'overall_result', 'scheduled_for',
        'started_at', 'completed_at', 'summary', 'photos',
    ];

    protected $casts = [
        'photos'        => 'array',
        'scheduled_for' => 'date',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];

    protected $appends = ['photo_urls'];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(InspectionRound::class, 'inspection_round_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(InspectionFinding::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function getPhotoUrlsAttribute(): array
    {
        return collect($this->photos ?? [])
            ->map(fn ($p) => Storage::url($p))
            ->values()
            ->all();
    }
}

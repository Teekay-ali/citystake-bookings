<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BookingGroup extends Model
{
    protected $fillable = [
        'reference', 'building_id', 'organization_id',
        'lead_name', 'lead_email', 'lead_phone', 'created_by',
    ];

    public static function generateReference(): string
    {
        return 'CS-GRP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

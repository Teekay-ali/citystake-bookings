<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class UnitType extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'name',
        'slug',
        'bedroom_type',
        'max_guests',
        'base_price_per_night',
        'cleaning_fee',
        'service_charge_percent',
        'description',
        'specific_amenities',
        'is_active',
    ];

    protected $casts = [
        'base_price_per_night' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'service_charge_percent' => 'decimal:2',
        'specific_amenities' => 'array',
        'is_active' => 'boolean',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('sort_order');
    }

    public function primaryImage(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->where('is_primary', true);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // Check if any unit of this type is available for date range
    public function hasAvailability(string $checkIn, string $checkOut): bool
    {
        $availableUnit = $this->findAvailableUnit($checkIn, $checkOut);
        return $availableUnit !== null;
    }

    // Find an available unit for booking (Option A: Auto-assign)
    public function findAvailableUnit(string $checkIn, string $checkOut): ?Unit
    {
        $availableUnits = $this->units()
            ->where('status', 'available')
            ->whereDoesntHave('bookings', function ($query) use ($checkIn, $checkOut) {
                $query->whereIn('status', ['confirmed', 'pending'])
                    ->where(function ($q) use ($checkIn, $checkOut) {
                        $q->whereBetween('check_in', [$checkIn, $checkOut])
                            ->orWhereBetween('check_out', [$checkIn, $checkOut])
                            ->orWhere(function ($subQ) use ($checkIn, $checkOut) {
                                $subQ->where('check_in', '<=', $checkIn)
                                    ->where('check_out', '>=', $checkOut);
                            });
                    });
            })
            ->whereDoesntHave('blockedDates', function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('blocked_date', [$checkIn, $checkOut]);
            })
            ->get();

        return $availableUnits->first();
    }

    // Get count of available units
    public function getAvailableUnitsCount(string $checkIn, string $checkOut): int
    {
        return $this->units()
            ->where('status', 'available')
            ->whereDoesntHave('bookings', function ($query) use ($checkIn, $checkOut) {
                $query->whereIn('status', ['confirmed', 'pending'])
                    ->where(function ($q) use ($checkIn, $checkOut) {
                        $q->whereBetween('check_in', [$checkIn, $checkOut])
                            ->orWhereBetween('check_out', [$checkIn, $checkOut])
                            ->orWhere(function ($subQ) use ($checkIn, $checkOut) {
                                $subQ->where('check_in', '<=', $checkIn)
                                    ->where('check_out', '>=', $checkOut);
                            });
                    });
            })
            ->whereDoesntHave('blockedDates', function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('blocked_date', [$checkIn, $checkOut]);
            })
            ->count();
    }
}

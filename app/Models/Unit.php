<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_type_id',
        'unit_number',
        'floor',
        'status',
        'notes',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(BlockedDate::class);
    }

    // Check if this specific unit is available
    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        $hasBooking = $this->bookings()
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            })
            ->exists();

        if ($hasBooking) return false;

        $hasBlockedDate = $this->blockedDates()
            ->whereBetween('blocked_date', [$checkIn, $checkOut])
            ->exists();

        return !$hasBlockedDate;
    }


    /**
     * Check if unit is blocked for given date range
     */
    public function isBlockedForDates($checkIn, $checkOut): bool
    {
        return $this->blockedDates()
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('blocked_from', '<=', $checkOut)
                    ->where('blocked_to', '>=', $checkIn);
            })
            ->exists();
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'bedroom_type', 'max_guests',
        'address', 'city', 'base_price_per_night', 'cleaning_fee',
        'service_charge_percent', 'is_active', 'amenities', 'house_rules'
    ];

    protected $casts = [
        'amenities' => 'array',
        'house_rules' => 'array',
        'base_price_per_night' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'service_charge_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(PropertyImage::class)->where('is_primary', true);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(BlockedDate::class);
    }

    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        // Check for existing bookings
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

        // Check for blocked dates
        $hasBlockedDate = $this->blockedDates()
            ->whereBetween('blocked_date', [$checkIn, $checkOut])
            ->exists();

        return !$hasBlockedDate;
    }
}

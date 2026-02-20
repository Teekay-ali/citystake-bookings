<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Cache;


class Building extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
        'city',
        'amenities',
        'house_rules',
        'is_active',
    ];

    protected $casts = [
        'amenities' => 'array',
        'house_rules' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        // Clear cache when buildings are created, updated, or deleted
        static::saved(function () {
            Cache::forget('active_buildings');
        });

        static::deleted(function () {
            Cache::forget('active_buildings');
        });
    }

    public function unitTypes(): HasMany
    {
        return $this->hasMany(UnitType::class);
    }

    public function units(): HasManyThrough
    {
        return $this->hasManyThrough(Unit::class, UnitType::class);
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

}

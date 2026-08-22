<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Simple key/value application settings. Values are JSON, so a setting can hold
 * a scalar, list, or object. Reads are cached forever and busted on write.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];

    private const MISSING = '__missing__';

    public static function get(string $key, $default = null)
    {
        $value = Cache::rememberForever(
            "setting:{$key}",
            fn () => static::query()->where('key', $key)->first()?->value ?? self::MISSING
        );

        return $value === self::MISSING ? $default : $value;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }
}

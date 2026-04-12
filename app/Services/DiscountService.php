<?php

namespace App\Services;

class DiscountService
{
    // Rule thresholds
    const LONG_STAY_NIGHTS    = 5;
    const LONG_STAY_PERCENT   = 5;

    const BULK_UNITS_COUNT    = 7;
    const BULK_UNITS_PERCENT  = 10;

    /**
     * Resolve the applicable discount for a booking.
     * Returns the highest qualifying discount only — rules don't stack.
     *
     * @param int $nights
     * @param int $unitsInSameBookingGroup  For future group bookings; pass 1 for single bookings
     * @return array{ type: string|null, percent: float }
     */
    public static function resolve(int $nights, int $unitCount = 1): array
    {
        $candidates = [];

        if ($unitCount >= self::BULK_UNITS_COUNT) {
            $candidates[] = ['type' => 'bulk_units', 'percent' => self::BULK_UNITS_PERCENT];
        }

        if ($nights >= self::LONG_STAY_NIGHTS) {
            $candidates[] = ['type' => 'long_stay', 'percent' => self::LONG_STAY_PERCENT];
        }

        if (empty($candidates)) {
            return ['type' => null, 'percent' => 0];
        }

        // Return the highest discount only
        return collect($candidates)->sortByDesc('percent')->first();
    }

    /**
     * Human-readable label for display and emails.
     */
    public static function label(string $type): string
    {
        return match($type) {
            'long_stay'  => 'Long stay discount (5+ nights)',
            'bulk_units' => 'Bulk booking discount (7+ units)',
            default      => 'Discount',
        };
    }
}

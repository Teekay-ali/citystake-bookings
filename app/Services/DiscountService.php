<?php

namespace App\Services;

class DiscountService
{
    // Rule thresholds
    const LONG_STAY_NIGHTS   = 7;   // "7+ nights"
    const MULTI_ROOM_COUNT   = 2;   // "more than 1 room"

    // Percentages
    const LONG_STAY_PERCENT  = 5;   // 7+ nights
    const MULTI_ROOM_PERCENT = 5;   // 2+ rooms, single individual/company
    const COMBINED_PERCENT   = 10;  // 2+ rooms AND 7+ nights

    /**
     * Resolve the applicable automatic discount for a booking.
     * Rules do not stack arbitrarily - the combined case is its own 10% tier:
     *
     *   - 5%  for 7+ nights
     *   - 5%  for more than 1 room by a single individual/company
     *   - 10% for more than 1 room AND 7+ nights
     *
     * @param int $nights
     * @param int $unitCount  Rooms booked by the same payer (a group booking's size); 1 for a single booking.
     * @return array{ type: string|null, percent: float }
     */
    public static function resolve(int $nights, int $unitCount = 1): array
    {
        $longStay  = $nights >= self::LONG_STAY_NIGHTS;
        $multiRoom = $unitCount >= self::MULTI_ROOM_COUNT;

        if ($longStay && $multiRoom) {
            return ['type' => 'multi_room_long_stay', 'percent' => self::COMBINED_PERCENT];
        }

        if ($multiRoom) {
            return ['type' => 'multi_room', 'percent' => self::MULTI_ROOM_PERCENT];
        }

        if ($longStay) {
            return ['type' => 'long_stay', 'percent' => self::LONG_STAY_PERCENT];
        }

        return ['type' => null, 'percent' => 0];
    }

    /**
     * Human-readable label for display and emails.
     */
    public static function label(string $type): string
    {
        return match($type) {
            'long_stay'            => 'Long-stay discount (7+ nights)',
            'multi_room'           => 'Multi-room discount (2+ rooms)',
            'multi_room_long_stay' => 'Multi-room long-stay discount (2+ rooms, 7+ nights)',
            default                => 'Discount',
        };
    }
}

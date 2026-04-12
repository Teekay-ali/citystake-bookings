<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Booking Configuration
    |--------------------------------------------------------------------------
    */

    // Minimum nights required for a booking
    'min_nights' => 1,

    // Maximum nights allowed for a booking
    'max_nights' => 90, // 3 months

    // Maximum days in advance a booking can be made
    'max_advance_days' => 365, // 1 year

    // Minimum hours before check-in that a booking can be made
    'min_hours_before_checkin' => 24, // 24 hours notice

    // Hours after which a pending payment booking is auto-cancelled
    'payment_timeout_hours' => 2,

    // Allow same-day checkout and check-in on different units
    'allow_same_day_turnover' => false,

    // Late checkout fixed fee (in Naira)
    'late_checkout_fee' => 20000,
];

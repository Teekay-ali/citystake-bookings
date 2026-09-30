<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Building;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $total = 150000;

        return [
            'booking_reference' => 'BK-' . strtoupper(Str::random(8)),
            'building_id'       => Building::factory(),
            'unit_type_id'      => UnitType::factory(),
            'unit_id'           => Unit::factory(),
            'user_id'           => User::factory(),
            'guest_name'        => fake()->name(),
            'guest_email'       => fake()->safeEmail(),
            'guest_phone'       => fake()->numerify('080########'),
            'check_in'          => now()->toDateString(),
            'check_out'         => now()->addDays(3)->toDateString(),
            'nights'            => 3,
            'guests'            => 2,
            'subtotal'          => $total,
            'service_charge'    => 0,
            'total_amount'      => $total,
            'status'            => 'confirmed',
            'payment_status'    => 'pending',
            'amount_received'   => 0,
        ];
    }
}

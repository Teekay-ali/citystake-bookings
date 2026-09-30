<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\UnitType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UnitTypeFactory extends Factory
{
    protected $model = UnitType::class;

    public function definition(): array
    {
        return [
            'building_id'          => Building::factory(),
            'name'                 => '2-Bedroom Apartment',
            'slug'                 => 'two-bed-' . fake()->unique()->numberBetween(1, 999999),
            'bedroom_type'         => '2-bed',
            'max_guests'           => 4,
            'base_price_per_night' => 50000,
            'is_active'            => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'unit_type_id' => UnitType::factory(),
            'unit_number'  => fake()->unique()->numerify('###'),
            'is_available' => true,
            'status'       => 'available',
        ];
    }
}

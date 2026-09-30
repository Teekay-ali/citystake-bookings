<?php

namespace Database\Factories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name'        => $name,
            'slug'        => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 999999),
            'description' => 'Test building',
            'address'     => fake()->streetAddress(),
            'city'        => 'Abuja',
            'is_active'   => true,
        ];
    }
}

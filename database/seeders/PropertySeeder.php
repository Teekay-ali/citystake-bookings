<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $properties = [
            [
                'name' => 'CityStake Lekki 2-Bedroom Apartment',
                'bedroom_type' => '2-bed',
                'max_guests' => 4,
                'address' => 'Lekki Phase 1, Lagos',
                'description' => 'Modern 2-bedroom apartment in the heart of Lekki with stunning views and premium amenities.',
                'base_price_per_night' => 45000.00,
                'cleaning_fee' => 5000.00,
                'amenities' => ['WiFi', 'Air Conditioning', 'Generator', 'Parking', 'Swimming Pool', 'Gym', 'Security'],
                'house_rules' => ['No smoking', 'No pets', 'No parties', 'Check-in after 2 PM', 'Check-out before 12 PM'],
            ],
            [
                'name' => 'CityStake VI 3-Bedroom Penthouse',
                'bedroom_type' => '3-bed',
                'max_guests' => 6,
                'address' => 'Victoria Island, Lagos',
                'description' => 'Luxurious 3-bedroom penthouse with panoramic city views and world-class facilities.',
                'base_price_per_night' => 75000.00,
                'cleaning_fee' => 8000.00,
                'amenities' => ['WiFi', 'Air Conditioning', 'Generator', 'Parking', 'Swimming Pool', 'Gym', 'Security', 'Smart TV', 'Netflix'],
                'house_rules' => ['No smoking', 'No pets', 'No parties', 'Check-in after 2 PM', 'Check-out before 12 PM'],
            ],
            [
                'name' => 'CityStake Ikoyi 4-Bedroom Mansion',
                'bedroom_type' => '4-bed',
                'max_guests' => 8,
                'address' => 'Banana Island, Ikoyi, Lagos',
                'description' => 'Premium 4-bedroom mansion with private pool and garden in exclusive Banana Island.',
                'base_price_per_night' => 120000.00,
                'cleaning_fee' => 10000.00,
                'amenities' => ['WiFi', 'Air Conditioning', 'Generator', 'Parking', 'Private Pool', 'Garden', 'Gym', 'Security', 'Smart Home', 'Chef Kitchen'],
                'house_rules' => ['No smoking indoors', 'No pets', 'Events require approval', 'Check-in after 3 PM', 'Check-out before 11 AM'],
            ],
        ];

        foreach ($properties as $propertyData) {
            $property = Property::create([
                ...$propertyData,
                'slug' => Str::slug($propertyData['name']),
            ]);

            // Create placeholder images (you'll replace these with real images later)
            for ($i = 1; $i <= 5; $i++) {
                PropertyImage::create([
                    'property_id' => $property->id,
                    'image_path' => 'https://placehold.co/800x600/png?text=' . urlencode($property->name),
                    'sort_order' => $i,
                    'is_primary' => $i === 1,
                ]);
            }
        }
    }
}

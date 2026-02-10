<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Image;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@citystake.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create Buildings with Unit Types and Units
        $this->createAsokoroBuilding();
        $this->createMaitamaBuilding();
        $this->createWuseBuilding();
    }

    private function createAsokoroBuilding()
    {
        $building = Building::create([
            'name' => 'CityStake Asokoro',
            'slug' => 'citystake-asokoro',
            'description' => 'Luxurious apartment complex in the prestigious Asokoro district with premium amenities and stunning views of Abuja.',
            'address' => 'Asokoro District',
            'city' => 'Abuja',
            'amenities' => ['WiFi', 'Air Conditioning', 'Generator', 'Parking', 'Swimming Pool', 'Gym', '24/7 Security'],
            'house_rules' => ['No smoking', 'No pets', 'No parties', 'Check-in after 2 PM', 'Check-out before 12 PM'],
        ]);

        // Add building images - Using real Unsplash images
        $buildingImages = [
            'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=600&fit=crop',
        ];

        foreach ($buildingImages as $index => $imageUrl) {
            Image::create([
                'imageable_id' => $building->id,
                'imageable_type' => Building::class,
                'image_path' => $imageUrl,
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
            ]);
        }

        // Create 2-Bed Unit Type (3 units)
        $unitType2Bed = UnitType::create([
            'building_id' => $building->id,
            'name' => '2-Bedroom Apartment',
            'slug' => '2-bedroom-apartment',
            'bedroom_type' => '2-bed',
            'max_guests' => 4,
            'base_price_per_night' => 45000.00,
            'cleaning_fee' => 5000.00,
            'service_charge_percent' => 10,
            'description' => 'Spacious 2-bedroom apartment with modern furnishings and city views.',
            'specific_amenities' => ['Smart TV', 'Netflix', 'Dishwasher', 'Washing Machine'],
        ]);

        // Add unit type images
        $unit2BedImages = [
            'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1502672260066-6bc176c62d85?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=800&h=600&fit=crop',
        ];

        foreach ($unit2BedImages as $index => $imageUrl) {
            Image::create([
                'imageable_id' => $unitType2Bed->id,
                'imageable_type' => UnitType::class,
                'image_path' => $imageUrl,
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
            ]);
        }

        // Create 3 units of 2-bed type
        Unit::create(['unit_type_id' => $unitType2Bed->id, 'unit_number' => 'A101', 'floor' => '1']);
        Unit::create(['unit_type_id' => $unitType2Bed->id, 'unit_number' => 'A201', 'floor' => '2']);
        Unit::create(['unit_type_id' => $unitType2Bed->id, 'unit_number' => 'A301', 'floor' => '3']);

        // Create 3-Bed Unit Type (2 units)
        $unitType3Bed = UnitType::create([
            'building_id' => $building->id,
            'name' => '3-Bedroom Apartment',
            'slug' => '3-bedroom-apartment',
            'bedroom_type' => '3-bed',
            'max_guests' => 6,
            'base_price_per_night' => 65000.00,
            'cleaning_fee' => 7000.00,
            'service_charge_percent' => 10,
            'description' => 'Luxurious 3-bedroom apartment perfect for families.',
        ]);

        $unit3BedImages = [
            'https://images.unsplash.com/photo-1600607687644-c7171b42498b?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600573472591-ee6b68d14c68?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600566752355-35792bedcfea?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=800&h=600&fit=crop',
        ];

        foreach ($unit3BedImages as $index => $imageUrl) {
            Image::create([
                'imageable_id' => $unitType3Bed->id,
                'imageable_type' => UnitType::class,
                'image_path' => $imageUrl,
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
            ]);
        }

        Unit::create(['unit_type_id' => $unitType3Bed->id, 'unit_number' => 'B101', 'floor' => '1']);
        Unit::create(['unit_type_id' => $unitType3Bed->id, 'unit_number' => 'B201', 'floor' => '2']);
    }

    private function createMaitamaBuilding()
    {
        $building = Building::create([
            'name' => 'CityStake Maitama',
            'slug' => 'citystake-maitama',
            'description' => 'Premium apartments in the heart of Maitama, Abuja\'s most exclusive diplomatic district with world-class facilities.',
            'address' => 'Maitama District',
            'city' => 'Abuja',
            'amenities' => ['WiFi', 'Air Conditioning', 'Generator', 'Parking', 'Swimming Pool', 'Gym', 'Concierge', 'Business Center'],
            'house_rules' => ['No smoking', 'No pets', 'No parties', 'Check-in after 2 PM', 'Check-out before 12 PM'],
        ]);

        $buildingImages = [
            'https://images.unsplash.com/photo-1613977257363-707ba9348227?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600585154084-4e5fe7c39198?w=800&h=600&fit=crop',
        ];

        foreach ($buildingImages as $index => $imageUrl) {
            Image::create([
                'imageable_id' => $building->id,
                'imageable_type' => Building::class,
                'image_path' => $imageUrl,
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
            ]);
        }

        // 2-Bed (2 units)
        $unitType2Bed = UnitType::create([
            'building_id' => $building->id,
            'name' => '2-Bedroom Deluxe',
            'slug' => '2-bedroom-deluxe',
            'bedroom_type' => '2-bed',
            'max_guests' => 4,
            'base_price_per_night' => 55000.00,
            'cleaning_fee' => 6000.00,
            'service_charge_percent' => 10,
            'description' => 'Deluxe 2-bedroom with premium finishes.',
        ]);

        $unit2BedImages = [
            'https://images.unsplash.com/photo-1600121848594-d8644e57abab?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1616137466211-f939a420be84?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1616594266537-c146c7f699b8?w=800&h=600&fit=crop',
        ];

        foreach ($unit2BedImages as $index => $imageUrl) {
            Image::create([
                'imageable_id' => $unitType2Bed->id,
                'imageable_type' => UnitType::class,
                'image_path' => $imageUrl,
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
            ]);
        }

        Unit::create(['unit_type_id' => $unitType2Bed->id, 'unit_number' => 'C501', 'floor' => '5']);
        Unit::create(['unit_type_id' => $unitType2Bed->id, 'unit_number' => 'C601', 'floor' => '6']);

        // 3-Bed Penthouse (1 unit)
        $unitType3Bed = UnitType::create([
            'building_id' => $building->id,
            'name' => '3-Bedroom Penthouse',
            'slug' => '3-bedroom-penthouse',
            'bedroom_type' => '3-bed',
            'max_guests' => 6,
            'base_price_per_night' => 95000.00,
            'cleaning_fee' => 10000.00,
            'service_charge_percent' => 10,
            'description' => 'Stunning penthouse with panoramic city views.',
        ]);

        $unit3BedImages = [
            'https://images.unsplash.com/photo-1600585152220-90363fe7e115?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600566752229-250ed79470a1?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600585154363-67eb9e2e2099?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600563438938-a9a27216b4f5?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&h=600&fit=crop',
        ];

        foreach ($unit3BedImages as $index => $imageUrl) {
            Image::create([
                'imageable_id' => $unitType3Bed->id,
                'imageable_type' => UnitType::class,
                'image_path' => $imageUrl,
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
            ]);
        }

        Unit::create(['unit_type_id' => $unitType3Bed->id, 'unit_number' => 'PH1', 'floor' => 'Penthouse']);
    }

    private function createWuseBuilding()
    {
        $building = Building::create([
            'name' => 'CityStake Wuse',
            'slug' => 'citystake-wuse',
            'description' => 'Modern residences in the vibrant Wuse commercial district with easy access to shopping, dining, and business centers.',
            'address' => 'Wuse Zone 2',
            'city' => 'Abuja',
            'amenities' => ['WiFi', 'Air Conditioning', 'Generator', 'Private Parking', 'Swimming Pool', 'Garden', 'Gym', 'Smart Home', '24/7 Security'],
            'house_rules' => ['No smoking indoors', 'No pets', 'Events require approval', 'Check-in after 3 PM', 'Check-out before 11 AM'],
        ]);

        $buildingImages = [
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600607687644-aac4c3eac7f4?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600566752355-35792bedcfea?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600573472591-ee6b68d14c68?w=800&h=600&fit=crop',
        ];

        foreach ($buildingImages as $index => $imageUrl) {
            Image::create([
                'imageable_id' => $building->id,
                'imageable_type' => Building::class,
                'image_path' => $imageUrl,
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
            ]);
        }

        // 4-Bed Luxury (2 units)
        $unitType4Bed = UnitType::create([
            'building_id' => $building->id,
            'name' => '4-Bedroom Luxury Apartment',
            'slug' => '4-bedroom-luxury-apartment',
            'bedroom_type' => '4-bed',
            'max_guests' => 8,
            'base_price_per_night' => 120000.00,
            'cleaning_fee' => 12000.00,
            'service_charge_percent' => 10,
            'description' => 'Ultra-luxury 4-bedroom apartment with premium amenities and spacious living areas.',
        ]);

        $unit4BedImages = [
            'https://images.unsplash.com/photo-1600047509358-9dc75507daeb?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=800&h=600&fit=crop',
        ];

        foreach ($unit4BedImages as $index => $imageUrl) {
            Image::create([
                'imageable_id' => $unitType4Bed->id,
                'imageable_type' => UnitType::class,
                'image_path' => $imageUrl,
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
            ]);
        }

        Unit::create(['unit_type_id' => $unitType4Bed->id, 'unit_number' => 'W101', 'floor' => 'Ground']);
        Unit::create(['unit_type_id' => $unitType4Bed->id, 'unit_number' => 'W102', 'floor' => 'Ground']);
    }
}

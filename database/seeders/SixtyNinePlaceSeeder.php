<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Database\Seeder;

class SixtyNinePlaceSeeder extends Seeder
{
    public function run(): void
    {
        if (Building::where('slug', '69place')->exists()) {
            $this->command->info('69place already seeded — skipping.');
            return;
        }

        $building = Building::create([
            'name'        => '69Place',
            'slug'        => '69place',
            'description' => 'Contemporary shortlet apartments in the heart of Abuja, offering studio, one-bedroom, and two-bedroom options with modern finishes and premium amenities.',
            'address'     => '69 Place',
            'city'        => 'Abuja',
            'amenities'   => [
                '24/7 Security',
                'Backup Generator',
                'Parking Space',
                'High-Speed Internet',
                'Water Supply',
                'CCTV Surveillance',
            ],
            'house_rules' => [
                'No smoking indoors',
                'No pets',
                'No parties or loud gatherings',
                'Check-in after 2 PM',
                'Check-out before 12 PM',
            ],
            'is_active' => true,
        ]);


        $studio = UnitType::create([
            'building_id'            => $building->id,
            'name'                   => 'Studio Apartment',
            'slug'                   => 'studio-apartment',
            'bedroom_type'           => 'studio',
            'max_guests'             => 2,
            'base_price_per_night'   => 115000.00,
            'cleaning_fee'           => 0.00,
            'service_charge_percent' => 0.00, // VAT
            'description'            => 'Compact and well-appointed studio apartments, ideal for solo travellers and couples.',
            'is_active'              => true,
        ]);

        foreach (['005', '008', '017', '020'] as $number) {
            Unit::create([
                'unit_type_id' => $studio->id,
                'unit_number'  => $number,
                'floor'        => null,
            ]);
        }

        // ── 1-Bedroom Apartments ──────────────────────────────────────
        $oneBed = UnitType::create([
            'building_id'            => $building->id,
            'name'                   => '1-Bedroom Apartment',
            'slug'                   => '1-bedroom-apartment',
            'bedroom_type'           => '1-bed',
            'max_guests'             => 2,
            'base_price_per_night'   => 130000.00,
            'cleaning_fee'           => 0.00,
            'service_charge_percent' => 0.00, // VAT
            'description'            => 'Spacious one-bedroom apartments with a separate living area, perfect for extended stays.',
            'is_active'              => true,
        ]);

        foreach (['004', '006', '007', '009', '016', '018', '019', '021'] as $number) {
            Unit::create([
                'unit_type_id' => $oneBed->id,
                'unit_number'  => $number,
                'floor'        => null,
            ]);
        }

        // ── 2-Bedroom Apartments ──────────────────────────────────────
        $twoBed = UnitType::create([
            'building_id'            => $building->id,
            'name'                   => '2-Bedroom Apartment',
            'slug'                   => '2-bedroom-apartment',
            'bedroom_type'           => '2-bed',
            'max_guests'             => 4,
            'base_price_per_night'   => 150000.00,
            'cleaning_fee'           => 0.00,
            'service_charge_percent' => 0.00, // VAT
            'description'            => 'Generously sized two-bedroom apartments, ideal for families and groups.',
            'is_active'              => true,
        ]);

        foreach (['001', '002', '003', '010', '011', '012', '013', '014', '015', '022', '023', '024'] as $number) {
            Unit::create([
                'unit_type_id' => $twoBed->id,
                'unit_number'  => $number,
                'floor'        => null,
            ]);
        }

        $this->command->info('69place seeded successfully — 3 unit types, 24 units.');
    }
}

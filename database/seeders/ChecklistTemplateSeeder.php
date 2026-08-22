<?php

namespace Database\Seeders;

use App\Models\ChecklistItem;
use Illuminate\Database\Seeder;

/**
 * Seeds the inspection checklist template from the client's spec. Idempotent
 * and additive (updateOrCreate on `key`), so it is safe to run on production
 * on its own — never via the full db:seed, which also runs the destructive
 * roles/permissions seeder.
 */
class ChecklistTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // ── Guest Personal Spaces (per unit) ──
            // Living Room
            ['key' => 'living_room.main_door', 'category' => 'personal', 'section' => 'living_room', 'scope' => 'unit', 'sort_order' => 1, 'label' => 'Main door & lock'],
            ['key' => 'living_room.remotes',   'category' => 'personal', 'section' => 'living_room', 'scope' => 'unit', 'sort_order' => 2, 'label' => 'AC & TV remotes'],
            ['key' => 'living_room.sofa',      'category' => 'personal', 'section' => 'living_room', 'scope' => 'unit', 'sort_order' => 3, 'label' => 'Sofa, table and rugs stain free'],
            ['key' => 'living_room.dining',    'category' => 'personal', 'section' => 'living_room', 'scope' => 'unit', 'sort_order' => 4, 'label' => 'Dining table and chairs'],
            ['key' => 'living_room.surfaces',  'category' => 'personal', 'section' => 'living_room', 'scope' => 'unit', 'sort_order' => 5, 'label' => 'Floors, windows, TV stands mopped, swept and dust free'],
            ['key' => 'living_room.lights',    'category' => 'personal', 'section' => 'living_room', 'scope' => 'unit', 'sort_order' => 6, 'label' => 'Lights are working everywhere'],

            // Kitchen
            ['key' => 'kitchen.appliances',   'category' => 'personal', 'section' => 'kitchen', 'scope' => 'unit', 'sort_order' => 7, 'label' => 'Microwave & fridge interior working, completely clean and free of odour/grease'],
            ['key' => 'kitchen.cleanliness',  'category' => 'personal', 'section' => 'kitchen', 'scope' => 'unit', 'sort_order' => 8, 'label' => 'The whole kitchen is clean and dust free'],

            // Bedroom (repeats per bedroom)
            ['key' => 'bedroom.bedding', 'category' => 'personal', 'section' => 'bedroom', 'scope' => 'unit', 'sort_order' => 9,  'repeats_per_bedroom' => true, 'label' => 'Crisp and ironed sheets, pillows fluffed, bedding aligned and arranged'],
            ['key' => 'bedroom.storage', 'category' => 'personal', 'section' => 'bedroom', 'scope' => 'unit', 'sort_order' => 10, 'repeats_per_bedroom' => true, 'label' => 'Under-bed clear of dust and dirt; closet cleaned & stocked with 8–10 matching hangers; towels prepped'],
            ['key' => 'bedroom.toilet',  'category' => 'personal', 'section' => 'bedroom', 'scope' => 'unit', 'sort_order' => 11, 'repeats_per_bedroom' => true, 'label' => 'Toilet: disinfected inside/out, paper folded to a point, full luxury amenities, sanitized bowl/seat, fresh spare roll, chrome flush button streak-free'],

            // ── Guest Common Spaces (per property) ──
            ['key' => 'common.lobbies', 'category' => 'common', 'section' => 'common', 'scope' => 'property', 'sort_order' => 1, 'label' => 'Main lobbies & reception, corridors, stairs & flooring'],
            ['key' => 'common.office',  'category' => 'common', 'section' => 'common', 'scope' => 'property', 'sort_order' => 2, 'label' => 'Office'],
            ['key' => 'common.gym',     'category' => 'common', 'section' => 'common', 'scope' => 'property', 'sort_order' => 3, 'label' => 'Gym: equipment, floor, safety & restroom clean and sanitized'],

            // ── Outdoor Space (per property) ──
            ['key' => 'outdoor.entrance',    'category' => 'outdoor', 'section' => 'outdoor', 'scope' => 'property', 'sort_order' => 1, 'label' => 'Main entrance & gate'],
            ['key' => 'outdoor.driveway',    'category' => 'outdoor', 'section' => 'outdoor', 'scope' => 'property', 'sort_order' => 2, 'label' => 'Driveway & parking'],
            ['key' => 'outdoor.pool',        'category' => 'outdoor', 'section' => 'outdoor', 'scope' => 'property', 'sort_order' => 3, 'label' => 'Pool & water feature'],
            ['key' => 'outdoor.lawn',        'category' => 'outdoor', 'section' => 'outdoor', 'scope' => 'property', 'sort_order' => 4, 'label' => 'Lawn & garden'],
            ['key' => 'outdoor.lighting',    'category' => 'outdoor', 'section' => 'outdoor', 'scope' => 'property', 'sort_order' => 5, 'label' => 'Exterior lighting'],
            ['key' => 'outdoor.bins',        'category' => 'outdoor', 'section' => 'outdoor', 'scope' => 'property', 'sort_order' => 6, 'label' => 'Outdoor bins & storage'],
            ['key' => 'outdoor.environment', 'category' => 'outdoor', 'section' => 'outdoor', 'scope' => 'property', 'sort_order' => 7, 'label' => 'Environment'],
        ];

        // Issue type per item, for recurring-failure analytics (grouping by
        // plumbing/electrical/… on top of the specific item).
        $issue = [
            'living_room.main_door' => 'safety',      'living_room.remotes' => 'appliance',
            'living_room.sofa' => 'furniture',        'living_room.dining' => 'furniture',
            'living_room.surfaces' => 'cleanliness',  'living_room.lights' => 'electrical',
            'kitchen.appliances' => 'appliance',      'kitchen.cleanliness' => 'cleanliness',
            'bedroom.bedding' => 'cleanliness',       'bedroom.storage' => 'furniture',
            'bedroom.toilet' => 'plumbing',
            'common.lobbies' => 'cleanliness',        'common.office' => 'cleanliness',
            'common.gym' => 'cleanliness',
            'outdoor.entrance' => 'general',          'outdoor.driveway' => 'general',
            'outdoor.pool' => 'plumbing',             'outdoor.lawn' => 'general',
            'outdoor.lighting' => 'electrical',       'outdoor.bins' => 'cleanliness',
            'outdoor.environment' => 'general',
        ];

        foreach ($items as $item) {
            ChecklistItem::updateOrCreate(
                ['key' => $item['key']],
                array_merge(
                    ['repeats_per_bedroom' => false, 'active' => true],
                    $item,
                    ['issue_category' => $issue[$item['key']] ?? 'general'],
                )
            );
        }
    }
}

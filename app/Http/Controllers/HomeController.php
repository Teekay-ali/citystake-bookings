<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Booking;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class HomeController extends Controller
{

    public static function clearPropertyCache(): void
    {
        Cache::forget('home_buildings');
        Cache::forget('home_stats');
    }

    public function index()
    {
        $buildings = Cache::remember('home_buildings', 3600, function () {
            return Building::with(['unitTypes' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('base_price_per_night')
                    ->select('id', 'building_id', 'name', 'bedroom_type', 'base_price_per_night', 'slug', 'max_guests');
            }, 'images'])
                ->where('is_active', true)
                ->get()
                ->map(function ($building) {
                    return [
                        'id'             => $building->id,
                        'name'           => $building->name,
                        'slug'           => $building->slug,
                        'address'        => $building->address,
                        'city'           => $building->city,
                        'description'    => $building->description,
                        'primary_image'  => $building->images->first()?->url ?? 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&h=600&fit=crop',
                        'unit_types'     => $building->unitTypes,
                        'starting_price' => $building->unitTypes->min('base_price_per_night'),
                    ];
                });
        });

        $stats = Cache::remember('home_stats', 3600, function () {
            return [
                'total_properties' => Building::where('is_active', true)->count(),
                'happy_guests'     => Booking::where('status', 'completed')->count(),
                'locations'        => Building::distinct('city')->count('city'),
            ];
        });

        // Active bookings for the logged-in guest - never cached
        $activeBookings = [];
        if (auth()->check()) {
            $activeBookings = Booking::where('user_id', auth()->id())
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->with(['building:id,name,slug', 'unitType:id,name,slug,building_id'])
                ->latest()
                ->get(['id', 'booking_reference', 'building_id', 'unit_type_id', 'status', 'payment_status', 'check_in', 'check_out'])
                ->toArray();
        }

        return Inertia::render('Home', [
            'buildings'      => $buildings,
            'stats'          => $stats,
            'activeBookings' => $activeBookings,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // Cache active buildings for 1 hour
        $buildings = Cache::remember('active_buildings', 3600, function () {
            return Building::with(['unitTypes' => function ($query) {
                $query->where('is_active', true)
                    ->select('id', 'building_id', 'name', 'bedroom_type', 'base_price_per_night', 'slug');
            }])
                ->where('is_active', true)
                ->get();
        });

        return Inertia::render('Home', [
            'buildings' => $buildings,
        ]);
    }
}

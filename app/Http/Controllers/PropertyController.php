<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Property::with(['primaryImage'])
            ->where('is_active', true);

        // Filter by bedroom type
        if ($request->filled('bedroom_type')) {
            $query->where('bedroom_type', $request->bedroom_type);
        }

        // Filter by max guests
        if ($request->filled('guests')) {
            $query->where('max_guests', '>=', $request->guests);
        }

        // Sort by price
        if ($request->filled('sort')) {
            if ($request->sort === 'price_asc') {
                $query->orderBy('base_price_per_night', 'asc');
            } elseif ($request->sort === 'price_desc') {
                $query->orderBy('base_price_per_night', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $properties = $query->paginate(9)->withQueryString();

        return Inertia::render('Properties/Index', [
            'properties' => $properties,
            'filters' => $request->only(['bedroom_type', 'guests', 'sort']),
        ]);
    }

    public function show(Property $property): Response
    {
        $property->load(['images', 'primaryImage']);

        return Inertia::render('Properties/Show', [
            'property' => $property,
        ]);
    }

    public function checkAvailability(Request $request, Property $property)
    {
        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $isAvailable = $property->isAvailable(
            $request->check_in,
            $request->check_out
        );

        return response()->json([
            'available' => $isAvailable,
            'message' => $isAvailable
                ? 'Property is available for selected dates'
                : 'Property is not available for selected dates'
        ]);
    }
}

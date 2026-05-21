<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function store(Request $request, Building $building, UnitType $unitType)
    {
        abort_unless(auth()->user()->can('manage-properties'), 403);

        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        $request->validate([
            'units'               => 'required|array|min:1',
            'units.*.unit_number' => 'required|string|max:20',
            'units.*.floor'       => 'nullable|string|max:20',
        ]);

        $existing = $unitType->units()->pluck('unit_number')->map(fn($n) => strtoupper($n))->toArray();
        $created  = 0;
        $skipped  = 0;

        foreach ($request->units as $data) {
            $number = strtoupper(trim($data['unit_number']));

            if (in_array($number, $existing)) {
                $skipped++;
                continue;
            }

            $unitType->units()->create([
                'unit_number' => $number,
                'floor'       => $data['floor'] ?? null,
            ]);

            $existing[] = $number;
            $created++;
        }

        $message = "{$created} unit(s) added.";
        if ($skipped > 0) {
            $message .= " {$skipped} duplicate(s) skipped.";
        }

        return back()->with('success', $message);
    }

    public function destroy(Building $building, UnitType $unitType, Unit $unit)
    {
        abort_unless(auth()->user()->can('manage-properties'), 403);

        if ($unitType->building_id !== $building->id || $unit->unit_type_id !== $unitType->id) {
            abort(404);
        }

        // Prevent deleting units with active or future bookings
        $hasActiveBookings = $unit->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->where('check_out', '>=', now())
            ->exists();

        if ($hasActiveBookings) {
            return back()->with('error', "Unit {$unit->unit_number} has active or upcoming bookings and cannot be deleted.");
        }

        $unit->delete();

        return back()->with('success', "Unit {$unit->unit_number} deleted.");
    }
}

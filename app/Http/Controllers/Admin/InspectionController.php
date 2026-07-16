<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\InspectionFinding;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Notifications\InspectionCompletedNotification;
use App\Services\NotificationService;
use App\Traits\ScopedByBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InspectionController extends Controller
{
    use ScopedByBuilding;

    // Statuses that mean a guest currently holds the unit (so it's NOT vacant).
    private const OCCUPYING_STATUSES = ['confirmed', 'checked_in', 'paused'];

    private const CATEGORIES = ['cleanliness', 'damage', 'electrical', 'plumbing', 'appliance', 'furniture', 'safety', 'other'];
    private const SEVERITIES = ['low', 'medium', 'high'];

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('view-inspections'), 403);

        $tab         = $request->input('tab', 'to_inspect');
        $buildingIds = $this->scopedBuildingIds();
        $vacant      = $this->vacantUnits($buildingIds);

        return Inertia::render('Admin/Inspections/Index', [
            'tab'       => $tab,
            'toInspect' => $tab === 'to_inspect' ? $vacant : [],
            'history'   => $tab === 'history' ? $this->history($buildingIds) : null,
            'stats'     => [
                'to_inspect'      => collect($vacant)->sum(fn ($b) => count($b['units'])),
                'in_progress'     => UnitInspection::whereIn('building_id', $buildingIds)->where('status', 'in_progress')->count(),
                'completed_week'  => UnitInspection::whereIn('building_id', $buildingIds)->where('status', 'completed')
                    ->where('completed_at', '>=', Carbon::now()->subDays(7))->count(),
                'open_concerns'   => InspectionFinding::whereHas('inspection', fn ($q) => $q->whereIn('building_id', $buildingIds))
                    ->where('resolved', false)->count(),
            ],
        ]);
    }

    /** Vacant units in accessible buildings, grouped by building, with last-inspected + in-progress. */
    private function vacantUnits(array $buildingIds): array
    {
        $today = Carbon::today();

        $units = Unit::where('status', 'available')
            ->whereHas('unitType', fn ($q) => $q->whereIn('building_id', $buildingIds))
            ->with('unitType:id,name,building_id,base_price_per_night', 'unitType.building:id,name')
            ->get();

        $occupied = Booking::whereIn('status', self::OCCUPYING_STATUSES)
            ->whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>', $today)
            ->pluck('unit_id')
            ->filter()
            ->unique();

        $vacant  = $units->reject(fn ($u) => $occupied->contains($u->id))->values();
        $unitIds = $vacant->pluck('id');

        $lastByUnit = UnitInspection::completed()
            ->whereIn('unit_id', $unitIds)
            ->selectRaw('unit_id, MAX(completed_at) as last_completed')
            ->groupBy('unit_id')
            ->pluck('last_completed', 'unit_id');

        $inProgress = UnitInspection::where('status', 'in_progress')
            ->whereIn('unit_id', $unitIds)
            ->pluck('id', 'unit_id');

        return $vacant
            ->groupBy(fn ($u) => $u->unitType->building->id)
            ->map(fn ($group) => [
                'building_id'   => $group->first()->unitType->building->id,
                'building_name' => $group->first()->unitType->building->name,
                'units'         => $group->map(fn ($u) => [
                    'id'                => $u->id,
                    'unit_number'       => $u->unit_number,
                    'floor'             => $u->floor,
                    'unit_type'         => $u->unitType->name,
                    'last_inspected_at' => $lastByUnit[$u->id] ?? null,
                    'in_progress_id'    => $inProgress[$u->id] ?? null,
                ])->values(),
            ])
            ->values()
            ->all();
    }

    private function history(array $buildingIds)
    {
        return UnitInspection::whereIn('building_id', $buildingIds)
            ->where('status', 'completed')
            ->with(['unit:id,unit_number', 'building:id,name', 'inspector:id,name'])
            ->withCount('findings')
            ->latest('completed_at')
            ->paginate(20)
            ->through(fn ($i) => [
                'id'             => $i->id,
                'unit_number'    => $i->unit?->unit_number,
                'building_name'  => $i->building?->name,
                'inspector'      => $i->inspector?->name,
                'overall_result' => $i->overall_result,
                'findings_count' => $i->findings_count,
                'completed_at'   => $i->completed_at,
            ]);
    }

    /** Start (or resume) an inspection for a vacant unit, then open the form. */
    public function start(Request $request)
    {
        abort_unless(auth()->user()->can('conduct-inspections'), 403);

        $data = $request->validate(['unit_id' => 'required|exists:units,id']);

        $unit = Unit::with('unitType')->findOrFail($data['unit_id']);
        abort_unless(in_array($unit->unitType->building_id, $this->scopedBuildingIds()), 403);

        $inspection = UnitInspection::firstOrCreate(
            ['unit_id' => $unit->id, 'status' => 'in_progress'],
            [
                'building_id'  => $unit->unitType->building_id,
                'inspector_id' => auth()->id(),
                'created_by'   => auth()->id(),
                'started_at'   => now(),
            ]
        );

        return redirect()->route('manage.inspections.show', $inspection->id);
    }

    public function show(UnitInspection $inspection)
    {
        abort_unless(auth()->user()->can('view-inspections'), 403);
        abort_unless(in_array($inspection->building_id, $this->scopedBuildingIds()), 403);

        $inspection->load(['unit.unitType', 'building:id,name', 'inspector:id,name', 'findings']);

        return Inertia::render('Admin/Inspections/Show', [
            'inspection' => [
                'id'             => $inspection->id,
                'status'         => $inspection->status,
                'overall_result' => $inspection->overall_result,
                'summary'        => $inspection->summary,
                'photos'         => collect($inspection->photos ?? [])->map(fn ($p) => ['path' => $p, 'url' => Storage::url($p)]),
                'unit_number'    => $inspection->unit?->unit_number,
                'unit_type'      => $inspection->unit?->unitType?->name,
                'building_name'  => $inspection->building?->name,
                'inspector'      => $inspection->inspector?->name,
                'started_at'     => $inspection->started_at,
                'completed_at'   => $inspection->completed_at,
                'findings'       => $inspection->findings->map(fn ($f) => [
                    'category'    => $f->category,
                    'severity'    => $f->severity,
                    'description' => $f->description,
                ]),
            ],
            'categories' => self::CATEGORIES,
            'severities' => self::SEVERITIES,
        ]);
    }

    public function update(Request $request, UnitInspection $inspection)
    {
        $this->authorizeEdit($inspection);
        $this->applyForm($request, $inspection);

        return back()->with('success', 'Inspection saved.');
    }

    public function complete(Request $request, UnitInspection $inspection)
    {
        $this->authorizeEdit($inspection);

        $this->applyForm($request, $inspection);

        if (! $inspection->overall_result) {
            return back()->with('error', 'Choose an overall result before completing.');
        }

        if ($inspection->overall_result === 'concerns' && $inspection->findings()->count() === 0) {
            return back()->with('error', 'Add at least one concern, or mark the inspection as OK.');
        }

        $inspection->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'inspector_id' => $inspection->inspector_id ?? auth()->id(),
        ]);

        // Notify CEO + super-admin.
        NotificationService::send(
            NotificationService::getUsersByRoles(['ceo', 'super-admin']),
            new InspectionCompletedNotification($inspection)
        );

        return redirect()->route('manage.inspections.index')
            ->with('success', 'Inspection completed. The CEO has been notified.');
    }

    private function authorizeEdit(UnitInspection $inspection): void
    {
        abort_unless(auth()->user()->can('conduct-inspections'), 403);
        abort_unless(in_array($inspection->building_id, $this->scopedBuildingIds()), 403);
        abort_if($inspection->status === 'completed', 422, 'This inspection is already completed.');
    }

    /** Save photos, overall result, summary and findings from the form (draft state). */
    private function applyForm(Request $request, UnitInspection $inspection): void
    {
        $validated = $request->validate([
            'overall_result'         => 'nullable|in:ok,concerns',
            'summary'                => 'nullable|string|max:2000',
            'photos'                 => 'nullable|array|max:12',
            'photos.*'               => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'remove_photos'          => 'nullable|array',
            'remove_photos.*'        => 'string',
            'findings'               => 'nullable|array|max:30',
            'findings.*.category'    => 'required|in:' . implode(',', self::CATEGORIES),
            'findings.*.severity'    => 'required|in:' . implode(',', self::SEVERITIES),
            'findings.*.description' => 'required|string|max:1000',
        ]);

        // Photos: keep existing minus removed, then append newly uploaded.
        $photos = collect($inspection->photos ?? []);
        if (! empty($validated['remove_photos'])) {
            foreach ($validated['remove_photos'] as $path) {
                Storage::disk('public')->delete($path);
            }
            $photos = $photos->reject(fn ($p) => in_array($p, $validated['remove_photos']));
        }
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photos->push($photo->store('inspections', 'public'));
            }
        }

        $result = $validated['overall_result'] ?? $inspection->overall_result;

        $inspection->update([
            'overall_result' => $result,
            'summary'        => $validated['summary'] ?? $inspection->summary,
            'photos'         => $photos->values()->all() ?: null,
            'started_at'     => $inspection->started_at ?? now(),
        ]);

        // Findings are the source of truth from the form: OK clears them,
        // concerns replaces them with the submitted set.
        $inspection->findings()->delete();
        if ($result === 'concerns' && ! empty($validated['findings'])) {
            foreach ($validated['findings'] as $f) {
                $inspection->findings()->create([
                    'category'    => $f['category'],
                    'severity'    => $f['severity'],
                    'description' => $f['description'],
                ]);
            }
        }
    }
}

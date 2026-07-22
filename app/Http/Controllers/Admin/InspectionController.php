<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\InspectionFinding;
use App\Models\InspectionRound;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Notifications\InspectionRoundCompletedNotification;
use App\Services\NotificationService;
use App\Traits\ScopedByBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InspectionController extends Controller
{
    use ScopedByBuilding;

    // Statuses that mean a guest currently holds the unit on a given date.
    private const OCCUPYING_STATUSES = ['confirmed', 'checked_in', 'paused'];

    private const CATEGORIES = ['cleanliness', 'damage', 'electrical', 'plumbing', 'appliance', 'furniture', 'safety', 'other'];
    private const SEVERITIES = ['low', 'medium', 'high'];

    // Unit states that a QC must resolve before a round can close.
    private const BLOCKING_STATES = ['pending', 'in_progress'];

    // ── Landing: today's rounds per property + history ──────────────
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('view-inspections'), 403);

        $tab         = $request->input('tab', 'today');
        $buildingIds = $this->scopedBuildingIds();
        $today       = Carbon::today();

        return Inertia::render('Admin/Inspections/Index', [
            'tab'     => $tab,
            'today'   => $tab === 'today' ? $this->todayCards($buildingIds, $today) : [],
            'history' => $tab === 'history' ? $this->history($buildingIds) : null,
            'stats'   => [
                'active_rounds'   => InspectionRound::whereIn('building_id', $buildingIds)
                    ->where('status', 'in_progress')->whereDate('round_date', $today)->count(),
                'inspected_today' => UnitInspection::whereIn('building_id', $buildingIds)
                    ->where('status', 'completed')->whereDate('completed_at', $today)->count(),
                'rounds_week'     => InspectionRound::whereIn('building_id', $buildingIds)
                    ->where('status', 'completed')->where('completed_at', '>=', $today->copy()->subDays(7))->count(),
                'open_concerns'   => InspectionFinding::whereHas('inspection', fn ($q) => $q->whereIn('building_id', $buildingIds))
                    ->where('resolved', false)->count(),
            ],
        ]);
    }

    /** One summary card per accessible property for today. */
    private function todayCards(array $buildingIds, Carbon $today): array
    {
        $buildings = \App\Models\Building::whereIn('id', $buildingIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $rounds = InspectionRound::whereIn('building_id', $buildingIds)
            ->whereDate('round_date', $today)
            ->get()
            ->keyBy('building_id');

        return $buildings->map(function ($b) use ($rounds, $today) {
            $round   = $rounds->get($b->id);
            $rows    = $this->unitRows($b->id, $round, $today);
            $counts  = $this->countStates($rows);

            return [
                'building_id'   => $b->id,
                'building_name' => $b->name,
                'round_id'      => $round?->id,
                'status'        => $round?->status,           // null | in_progress | completed | cancelled
                'total'         => count($rows),
                'inspectable'   => $counts['inspectable'],
                'inspected'     => $counts['inspected'],
                'pending'       => $counts['pending'],
                'occupied'      => $counts['occupied'],
                'concerns'      => $counts['concerns'],
            ];
        })->all();
    }

    private function history(array $buildingIds)
    {
        return InspectionRound::whereIn('building_id', $buildingIds)
            ->whereIn('status', ['completed', 'cancelled'])
            ->with(['building:id,name', 'completedBy:id,name'])
            ->withCount(['unitInspections as inspected_count' => fn ($q) => $q->where('status', 'completed')])
            ->latest('round_date')
            ->latest('id')
            ->paginate(15)
            ->through(fn ($r) => [
                'id'             => $r->id,
                'building_name'  => $r->building?->name,
                'round_date'     => $r->round_date,
                'status'         => $r->status,
                'inspected'      => $r->inspected_count,
                'completed_by'   => $r->completedBy?->name,
                'concerns'       => InspectionFinding::whereIn(
                    'unit_inspection_id',
                    $r->unitInspections()->where('status', 'completed')->pluck('id')
                )->count(),
            ]);
    }

    // ── Round lifecycle ─────────────────────────────────────────────

    /** Open (creating if needed) today's round for a property. */
    public function openRound(Request $request)
    {
        abort_unless(auth()->user()->can('conduct-inspections'), 403);

        $data = $request->validate(['building_id' => 'required|exists:buildings,id']);
        abort_unless(in_array((int) $data['building_id'], $this->scopedBuildingIds()), 403);

        $round = InspectionRound::firstOrCreate(
            ['building_id' => $data['building_id'], 'round_date' => Carbon::today()],
            ['status' => 'in_progress', 'started_by' => auth()->id()]
        );

        // Reopen a round that was cancelled earlier today (unique per day).
        if (! $round->wasRecentlyCreated && $round->status === 'cancelled') {
            $round->update(['status' => 'in_progress', 'started_by' => auth()->id()]);
        }

        return redirect()->route('manage.inspections.round', $round->id);
    }

    /** Round detail: the unit checklist. */
    public function round(InspectionRound $round)
    {
        abort_unless(auth()->user()->can('view-inspections'), 403);
        abort_unless(in_array($round->building_id, $this->scopedBuildingIds()), 403);

        $round->load('building:id,name', 'completedBy:id,name');

        $rows   = $this->unitRows($round->building_id, $round, Carbon::parse($round->round_date));
        $counts = $this->countStates($rows);

        return Inertia::render('Admin/Inspections/Round', [
            'round' => [
                'id'            => $round->id,
                'building_name' => $round->building?->name,
                'round_date'    => $round->round_date,
                'status'        => $round->status,
                'completed_by'  => $round->completedBy?->name,
                'completed_at'  => $round->completed_at,
                'note'          => $round->note,
            ],
            'units'  => $rows,
            'counts' => $counts,
            // A round can close once nothing inspectable is still outstanding.
            'canComplete' => $round->status === 'in_progress' && $counts['pending'] === 0,
        ]);
    }

    public function cancelRound(InspectionRound $round)
    {
        abort_unless(auth()->user()->can('conduct-inspections'), 403);
        abort_unless(in_array($round->building_id, $this->scopedBuildingIds()), 403);
        abort_unless($round->status === 'in_progress', 422, 'Only an active round can be cancelled.');

        $round->update(['status' => 'cancelled']);

        return redirect()->route('manage.inspections.index')->with('success', 'Round discarded.');
    }

    public function completeRound(InspectionRound $round)
    {
        abort_unless(auth()->user()->can('conduct-inspections'), 403);
        abort_unless(in_array($round->building_id, $this->scopedBuildingIds()), 403);
        abort_unless($round->status === 'in_progress', 422, 'This round is not active.');

        $rows   = $this->unitRows($round->building_id, $round, Carbon::parse($round->round_date));
        $counts = $this->countStates($rows);

        if ($counts['pending'] > 0) {
            return back()->with('error', 'Every vacant unit must be inspected before completing the round.');
        }

        $round->update([
            'status'       => 'completed',
            'completed_by' => auth()->id(),
            'completed_at' => now(),
        ]);

        NotificationService::send(
            NotificationService::getUsersByRoles(['ceo', 'super-admin']),
            new InspectionRoundCompletedNotification($round, $counts['inspected'], $counts['concerns'])
        );

        return redirect()->route('manage.inspections.index')
            ->with('success', 'Round completed. The CEO has been notified.');
    }

    // ── Per-unit inspection ─────────────────────────────────────────

    /** Start (or resume) a unit's inspection within a round, then open the form. */
    public function start(Request $request)
    {
        abort_unless(auth()->user()->can('conduct-inspections'), 403);

        $data = $request->validate([
            'round_id' => 'required|exists:inspection_rounds,id',
            'unit_id'  => 'required|exists:units,id',
        ]);

        $round = InspectionRound::findOrFail($data['round_id']);
        abort_unless(in_array($round->building_id, $this->scopedBuildingIds()), 403);
        abort_unless($round->status === 'in_progress', 422, 'This round is no longer active.');

        $unit = Unit::with('unitType')->findOrFail($data['unit_id']);
        abort_unless($unit->unitType->building_id === $round->building_id, 403);

        $inspection = UnitInspection::firstOrCreate(
            ['inspection_round_id' => $round->id, 'unit_id' => $unit->id],
            [
                'building_id'  => $round->building_id,
                'inspector_id' => auth()->id(),
                'created_by'   => auth()->id(),
                'status'       => 'in_progress',
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
                'round_id'       => $inspection->inspection_round_id,
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

    /** Finish a single unit; returns to the round (the round is what notifies the CEO). */
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

        if ($inspection->inspection_round_id) {
            return redirect()->route('manage.inspections.round', $inspection->inspection_round_id)
                ->with('success', "Unit {$inspection->unit?->unit_number} inspected.");
        }

        return redirect()->route('manage.inspections.index')->with('success', 'Inspection saved.');
    }

    private function authorizeEdit(UnitInspection $inspection): void
    {
        abort_unless(auth()->user()->can('conduct-inspections'), 403);
        abort_unless(in_array($inspection->building_id, $this->scopedBuildingIds()), 403);
        abort_if($inspection->status === 'completed', 422, 'This inspection is already completed.');
    }

    // ── Shared helpers ──────────────────────────────────────────────

    /**
     * Every unit in a property with its state for the given round/date.
     * States: occupied | offline | pending | in_progress | ok | concern
     */
    private function unitRows(int $buildingId, ?InspectionRound $round, Carbon $date): array
    {
        $units = Unit::whereHas('unitType', fn ($q) => $q->where('building_id', $buildingId))
            ->with('unitType:id,name,building_id')
            ->orderBy('unit_number')
            ->get();

        $unitIds = $units->pluck('id');

        $occupied = Booking::whereIn('unit_id', $unitIds)
            ->whereIn('status', self::OCCUPYING_STATUSES)
            ->whereDate('check_in', '<=', $date)
            ->whereDate('check_out', '>', $date)
            ->pluck('unit_id')
            ->unique();

        $inspections = $round
            ? UnitInspection::where('inspection_round_id', $round->id)
                ->withCount('findings')->get()->keyBy('unit_id')
            : collect();

        return $units->map(function ($u) use ($occupied, $inspections) {
            $insp  = $inspections->get($u->id);
            $state = 'pending';

            if ($insp && $insp->status === 'completed') {
                $state = $insp->overall_result === 'concerns' ? 'concern' : 'ok';
            } elseif ($insp && $insp->status === 'in_progress') {
                $state = 'in_progress';
            } elseif ($occupied->contains($u->id)) {
                $state = 'occupied';
            } elseif ($u->status !== 'available') {
                $state = 'offline';
            }

            return [
                'unit_id'        => $u->id,
                'unit_number'    => $u->unit_number,
                'floor'          => $u->floor,
                'unit_type'      => $u->unitType->name,
                'state'          => $state,
                'inspection_id'  => $insp?->id,
                'findings_count' => $insp?->findings_count ?? 0,
            ];
        })->all();
    }

    private function countStates(array $rows): array
    {
        $states = collect($rows)->pluck('state');

        $inspected = $states->filter(fn ($s) => in_array($s, ['ok', 'concern']))->count();
        $pending   = $states->filter(fn ($s) => in_array($s, self::BLOCKING_STATES))->count();
        $occupied  = $states->filter(fn ($s) => in_array($s, ['occupied', 'offline']))->count();

        $concerns = collect($rows)
            ->filter(fn ($r) => $r['state'] === 'concern')
            ->sum('findings_count');

        return [
            'inspected'   => $inspected,
            'pending'     => $pending,
            'occupied'    => $occupied,
            'inspectable' => count($rows) - $occupied,
            'concerns'    => $concerns,
        ];
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

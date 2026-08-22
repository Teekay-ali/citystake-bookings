<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Building;
use App\Models\InspectionItemResult;
use App\Models\InspectionRound;
use App\Models\MaintenanceReport;
use App\Models\RoundSectionInspection;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Models\UnitTurnover;
use App\Services\UnitTurnoverService;
use App\Notifications\InspectionRoundCompletedNotification;
use App\Notifications\UnitBlockedNotification;
use App\Services\InspectionChecklistService;
use App\Services\NotificationService;
use App\Traits\ScopedByBuilding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InspectionController extends Controller
{
    use ScopedByBuilding;

    public function __construct(
        private InspectionChecklistService $checklist,
        private UnitTurnoverService $turnovers,
    ) {
    }

    // The two property-level sections inspected once per round.
    private const PROPERTY_SECTIONS = ['common', 'outdoor'];

    // Statuses that mean a guest currently holds the unit on a given date.
    private const OCCUPYING_STATUSES = ['confirmed', 'checked_in', 'paused'];

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
            $spaces  = $round ? $this->sectionCards($round) : collect();

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
                // Readiness breakdown chips (req 3).
                'readiness'     => [
                    'ready_for_qa'   => $counts['ready_for_qa'],
                    'cleaning'       => $counts['cleaning'],
                    'needs_cleaning' => $counts['needs_cleaning'],
                    'qa_in_progress' => $counts['qa_in_progress'],
                    'guest_ready'    => $counts['guest_ready'],
                    'blocked'        => $counts['blocked'],
                ],
                // Property-wide spaces (common + outdoor), inspected once per round.
                'spaces_total'  => count(self::PROPERTY_SECTIONS),
                'spaces_done'   => $spaces->where('status', 'completed')->count(),
                'spaces'        => $spaces->map(fn ($s) => [
                    'section' => $s['section'],
                    'status'  => $s['status'],
                    'result'  => $s['result'],
                ])->values(),
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
                'concerns'       => $this->failedItemsQuery(
                    $r->unitInspections()->where('status', 'completed')->pluck('id'),
                    $r->sectionInspections()->pluck('id'),
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

        // Ensure the two property-level sections exist for this round.
        foreach (self::PROPERTY_SECTIONS as $section) {
            RoundSectionInspection::firstOrCreate(
                ['inspection_round_id' => $round->id, 'section' => $section],
                ['building_id' => $round->building_id, 'status' => 'pending']
            );
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
        $sections = $this->sectionCards($round);

        $sectionsDone = $sections->every(fn ($s) => $s['status'] === 'completed');

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
            'units'    => $rows,
            'sections' => $sections,
            'counts'   => $counts,
            'concerns' => $this->roundConcerns($round),
            // A round closes once every inspectable unit AND both property
            // sections are done.
            'canComplete' => $round->status === 'in_progress' && $counts['pending'] === 0 && $sectionsDone,
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

        if ($this->sectionCards($round)->contains(fn ($s) => $s['status'] !== 'completed')) {
            return back()->with('error', 'Complete the common and outdoor space checklists before closing the round.');
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

        // Lay down the checklist rows (bedroom items multiplied per bedroom).
        $inspection->load('unit.unitType');
        $this->checklist->seed($inspection, $this->checklist->unitPlan($inspection));

        // Advance the turnover (if the unit came through cleaning) into QA.
        $turnover = $this->turnovers->activeFor($unit);
        if ($turnover && $turnover->status === 'cleaning_completed') {
            $this->turnovers->startQa($turnover, $inspection);
        }

        return redirect()->route('manage.inspections.show', $inspection->id);
    }

    public function show(UnitInspection $inspection)
    {
        abort_unless(auth()->user()->can('view-inspections'), 403);
        abort_unless(in_array($inspection->building_id, $this->scopedBuildingIds()), 403);

        $inspection->load(['unit.unitType', 'building:id,name', 'inspector:id,name']);

        // Self-heal: a resumed inspection (or one predating a template change)
        // gets any missing checklist rows laid down before rendering.
        if ($inspection->status !== 'completed') {
            $this->checklist->seed($inspection, $this->checklist->unitPlan($inspection));
        }

        return Inertia::render('Admin/Inspections/Show', [
            'inspection' => [
                'id'             => $inspection->id,
                'round_id'       => $inspection->inspection_round_id,
                'status'         => $inspection->status,
                'overall_result' => $inspection->overall_result,
                'score'          => $inspection->score,
                'unit_number'    => $inspection->unit?->unit_number,
                'unit_type'      => $inspection->unit?->unitType?->name,
                'building_name'  => $inspection->building?->name,
                'inspector'      => $inspection->inspector?->name,
                'started_at'     => $inspection->started_at,
                'completed_at'   => $inspection->completed_at,
                'groups'         => $this->checklist->grouped($inspection),
                'progress'       => $this->checklist->progress($inspection),
            ],
        ]);
    }

    public function update(Request $request, UnitInspection $inspection)
    {
        $this->authorizeEdit($inspection);
        $this->saveResults($request, $inspection);

        return back()->with('success', 'Inspection saved.');
    }

    /** Finish a single unit; returns to the round (the round is what notifies the CEO). */
    public function complete(Request $request, UnitInspection $inspection)
    {
        $this->authorizeEdit($inspection);
        $this->saveResults($request, $inspection);

        if ($error = $this->checklist->completionError($inspection)) {
            return back()->with('error', $error);
        }

        $passed = $this->checklist->deriveResult($inspection) === 'pass';

        $inspection->update([
            // pass/fail derived from items → the legacy ok/concerns verdict the
            // round list still reads.
            'overall_result' => $passed ? 'ok' : 'concerns',
            'score'          => $this->checklist->score($inspection),
            'status'         => 'completed',
            'completed_at'   => now(),
            'inspector_id'   => $inspection->inspector_id ?? auth()->id(),
        ]);

        // A clean pass makes the unit guest-ready; a fail stays in QA until it's
        // re-cleaned or blocked (Phase D).
        $turnover = $inspection->turnover;
        if ($passed && $turnover && $turnover->status === 'qa_in_progress') {
            $this->turnovers->completeQa($turnover);
        }

        if ($inspection->inspection_round_id) {
            return redirect()->route('manage.inspections.round', $inspection->inspection_round_id)
                ->with('success', "Unit {$inspection->unit?->unit_number} inspected.");
        }

        return redirect()->route('manage.inspections.index')->with('success', 'Inspection saved.');
    }

    /** One-click branded PDF report for a completed inspection. */
    public function report(UnitInspection $inspection)
    {
        abort_unless(auth()->user()->can('view-inspections'), 403);
        abort_unless(in_array($inspection->building_id, $this->scopedBuildingIds()), 403);

        $inspection->load(['unit.unitType', 'building:id,name', 'inspector:id,name']);

        // Grouped results with photos inlined as base64 (dompdf can't fetch URLs).
        $groups = collect($this->checklist->grouped($inspection))->map(function ($g) {
            $g['items'] = collect($g['items'])->map(function ($i) {
                $i['photos'] = collect($i['photos'])->map(function ($p) {
                    $path = is_array($p) ? ($p['path'] ?? null) : $p;
                    $abs  = $path ? Storage::disk('public')->path($path) : null;
                    if (! $abs || ! is_file($abs)) {
                        return null;
                    }
                    return 'data:' . (mime_content_type($abs) ?: 'image/jpeg') . ';base64,' . base64_encode(file_get_contents($abs));
                })->filter()->values()->all();
                return $i;
            })->all();
            return $g;
        })->all();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.inspection', [
            'inspection'    => $inspection,
            'groups'        => $groups,
            'score'         => $inspection->score,
            'inspectorRole' => $inspection->inspector?->getRoleNames()->first(),
            'generatedAt'   => now(),
        ]);

        $filename = 'inspection-unit-' . ($inspection->unit?->unit_number ?? 'x')
            . '-' . optional($inspection->completed_at)->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /** Flag a unit for maintenance: block dates + optional maintenance request. */
    public function blockUnit(Request $request, UnitInspection $inspection)
    {
        abort_unless(auth()->user()->can('conduct-inspections'), 403);
        abort_unless(in_array($inspection->building_id, $this->scopedBuildingIds()), 403);

        $data = $request->validate([
            'blocked_from'      => 'required|date',
            'blocked_to'        => 'required|date|after_or_equal:blocked_from',
            'reason'            => 'required|string|max:255',
            'raise_maintenance' => 'boolean',
        ]);

        $inspection->loadMissing('unit', 'building');
        $unit = $inspection->unit;

        $blocked = $this->turnovers->blockUnit(
            $unit, $data['blocked_from'], $data['blocked_to'], $data['reason'], auth()->user()
        );

        $raised = false;
        if (! empty($data['raise_maintenance'])) {
            MaintenanceReport::create([
                'building_id'  => $inspection->building_id,
                'submitted_by' => auth()->id(),
                'title'        => "Unit {$unit->unit_number}: {$data['reason']}",
                'issue_type'   => 'other',
                'description'  => "Flagged during a QC inspection. {$data['reason']}",
                'location'     => "Unit {$unit->unit_number}",
                'status'       => 'pending',
            ]);
            $raised = true;
        }

        NotificationService::send(
            NotificationService::getUsersByRoles(['manager', 'super-admin', 'ceo'], $inspection->building_id),
            new UnitBlockedNotification($blocked, $unit->unit_number, $inspection->building?->name ?? '', $raised),
        );

        $back = $inspection->inspection_round_id
            ? redirect()->route('manage.inspections.round', $inspection->inspection_round_id)
            : redirect()->route('manage.inspections.index');

        return $back->with('success', "Unit {$unit->unit_number} blocked for maintenance"
            . ($raised ? ' and a maintenance request was raised.' : '.'));
    }

    // ── Per-property section inspection (common | outdoor) ───────────

    public function section(RoundSectionInspection $section)
    {
        abort_unless(auth()->user()->can('view-inspections'), 403);
        abort_unless(in_array($section->building_id, $this->scopedBuildingIds()), 403);

        $section->load(['round:id,round_date,status', 'building:id,name', 'inspector:id,name']);

        if ($section->status !== 'completed') {
            $this->checklist->seed($section, $this->checklist->sectionPlan($section));
            if ($section->status === 'pending' && auth()->user()->can('conduct-inspections')) {
                $section->update(['status' => 'in_progress', 'inspector_id' => auth()->id()]);
            }
        }

        return Inertia::render('Admin/Inspections/Section', [
            'section' => [
                'id'            => $section->id,
                'round_id'      => $section->inspection_round_id,
                'section'       => $section->section,
                'title'         => $section->section === 'common' ? 'Guest Common Spaces' : 'Outdoor Space',
                'status'        => $section->status,
                'result'        => $section->result,
                'building_name' => $section->building?->name,
                'inspector'     => $section->inspector?->name,
                'completed_at'  => $section->completed_at,
                'groups'        => $this->checklist->grouped($section),
                'progress'      => $this->checklist->progress($section),
            ],
        ]);
    }

    public function sectionUpdate(Request $request, RoundSectionInspection $section)
    {
        $this->authorizeSectionEdit($section);
        $this->saveResults($request, $section);

        return back()->with('success', 'Checklist saved.');
    }

    public function sectionComplete(Request $request, RoundSectionInspection $section)
    {
        $this->authorizeSectionEdit($section);
        $this->saveResults($request, $section);

        if ($error = $this->checklist->completionError($section)) {
            return back()->with('error', $error);
        }

        $section->update([
            'result'       => $this->checklist->deriveResult($section),
            'status'       => 'completed',
            'completed_at' => now(),
            'inspector_id' => $section->inspector_id ?? auth()->id(),
        ]);

        return redirect()->route('manage.inspections.round', $section->inspection_round_id)
            ->with('success', ucfirst($section->section).' spaces inspected.');
    }

    // ── Per-item photo upload (incremental, small requests) ──────────

    public function uploadResultPhotos(Request $request, InspectionItemResult $result)
    {
        $this->authorizeResult($result);

        $validated = $request->validate([
            'photos'   => 'required|array|max:6',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $photos = collect($result->photos ?? []);
        foreach ($validated['photos'] as $photo) {
            $photos->push($photo->store('inspections', 'public'));
        }
        $result->update(['photos' => $photos->values()->all()]);

        if ($request->wantsJson()) {
            return response()->json(['photos' => $this->photoPayload($result)]);
        }

        return back()->with('success', 'Photo added.');
    }

    public function deleteResultPhoto(Request $request, InspectionItemResult $result)
    {
        $this->authorizeResult($result);

        $data = $request->validate(['path' => 'required|string']);

        if (in_array($data['path'], $result->photos ?? [], true)) {
            Storage::disk('public')->delete($data['path']);
            $result->update([
                'photos' => collect($result->photos)->reject(fn ($p) => $p === $data['path'])->values()->all() ?: null,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json(['photos' => $this->photoPayload($result)]);
        }

        return back()->with('success', 'Photo removed.');
    }

    private function photoPayload(InspectionItemResult $result): array
    {
        return collect($result->photos ?? [])
            ->map(fn ($p) => ['path' => $p, 'url' => Storage::url($p)])
            ->values()->all();
    }

    // ── Authorization + result persistence ──────────────────────────

    private function authorizeEdit(UnitInspection $inspection): void
    {
        abort_unless(auth()->user()->can('conduct-inspections'), 403);
        abort_unless(in_array($inspection->building_id, $this->scopedBuildingIds()), 403);
        abort_if($inspection->status === 'completed', 422, 'This inspection is already completed.');
    }

    private function authorizeSectionEdit(RoundSectionInspection $section): void
    {
        abort_unless(auth()->user()->can('conduct-inspections'), 403);
        abort_unless(in_array($section->building_id, $this->scopedBuildingIds()), 403);
        abort_if($section->status === 'completed', 422, 'This section is already completed.');
    }

    /** A photo belongs to whichever inspectable (unit or section) owns its result row. */
    private function authorizeResult(InspectionItemResult $result): void
    {
        abort_unless(auth()->user()->can('conduct-inspections'), 403);

        $owner = $result->inspectable;
        abort_unless($owner && in_array($owner->building_id, $this->scopedBuildingIds()), 403);
        abort_if(($owner->status ?? null) === 'completed', 422, 'This inspection is already completed.');
    }

    /**
     * Persist per-item pass/fail/na + note. Photos are handled by their own
     * incremental endpoint, so a save never carries file uploads.
     */
    private function saveResults(Request $request, Model $inspectable): void
    {
        $validated = $request->validate([
            'results'          => 'nullable|array',
            'results.*.id'     => 'required|integer',
            'results.*.result' => 'nullable|in:pass,fail,na',
            'results.*.note'   => 'nullable|string|max:1000',
        ]);

        if (empty($validated['results'])) {
            return;
        }

        // Only rows that actually belong to this inspectable can be touched.
        $owned = $inspectable->itemResults()
            ->whereIn('id', collect($validated['results'])->pluck('id'))
            ->get()->keyBy('id');

        foreach ($validated['results'] as $row) {
            $target = $owned->get($row['id']);
            if (! $target) {
                continue;
            }
            $target->update([
                'result' => $row['result'] ?? null,
                'note'   => $row['note'] ?? null,
            ]);
        }
    }

    // ── Shared helpers ──────────────────────────────────────────────

    /**
     * Every unit in a property with its state for the given round/date.
     * States: occupied | offline | pending | in_progress | ok | concern
     */
    /**
     * Every unit in a property with its turnover-aware readiness state and the
     * relevant checkout / next-arrival times.
     * States: occupied | offline | blocked | needs_cleaning | cleaning |
     *         ready_for_qa | qa_in_progress | ok | concern | ready
     */
    private function unitRows(int $buildingId, ?InspectionRound $round, Carbon $date): array
    {
        $building = Building::find($buildingId);

        $units = Unit::whereHas('unitType', fn ($q) => $q->where('building_id', $buildingId))
            ->with('unitType:id,name,building_id')
            ->orderBy('unit_number')
            ->get();
        $unitIds = $units->pluck('id');

        $occupied = Booking::whereIn('unit_id', $unitIds)
            ->whereIn('status', self::OCCUPYING_STATUSES)
            ->whereDate('check_in', '<=', $date)->whereDate('check_out', '>', $date)
            ->pluck('unit_id')->unique();

        $lastCheckout = Booking::whereIn('unit_id', $unitIds)
            ->whereDate('check_out', '<=', $date)->whereNotIn('status', ['cancelled'])
            ->get(['id', 'unit_id', 'check_out'])
            ->groupBy('unit_id')->map(fn ($g) => $g->sortByDesc('check_out')->first());

        $nextArrival = Booking::whereIn('unit_id', $unitIds)
            ->whereIn('status', ['confirmed', 'pending'])->whereDate('check_in', '>=', $date)
            ->orderBy('check_in')->get(['id', 'unit_id', 'check_in'])
            ->groupBy('unit_id')->map->first();

        $turnovers = UnitTurnover::whereIn('unit_id', $unitIds)->get()
            ->groupBy('unit_id')->map(fn ($g) => $g->sortByDesc('id')->first());

        $blocked = BlockedDate::whereIn('unit_id', $unitIds)
            ->whereDate('blocked_from', '<=', $date)->whereDate('blocked_to', '>=', $date)
            ->pluck('unit_id')->unique();

        $inspections = $round
            ? UnitInspection::where('inspection_round_id', $round->id)
                ->withCount(['itemResults as concern_count' => fn ($q) => $q->where('result', 'fail')])
                ->get()->keyBy('unit_id')
            : collect();

        return $units->map(function ($u) use ($occupied, $lastCheckout, $nextArrival, $turnovers, $blocked, $inspections, $building) {
            $insp = $inspections->get($u->id);
            $to   = $turnovers->get($u->id);
            $out  = $lastCheckout->get($u->id);
            $arr  = $nextArrival->get($u->id);
            $active = $to && in_array($to->status, UnitTurnover::ACTIVE_STATUSES, true);

            $needsCleaning = $out
                && ! ($to && $to->ready_at && $to->ready_at->gte(Carbon::parse($out->check_out)->endOfDay()));

            // The round's own inspection wins once QC has touched the unit today.
            $state = match (true) {
                $blocked->contains($u->id) || ($to && $to->status === 'blocked') => 'blocked',
                $insp && $insp->status === 'completed'   => $insp->overall_result === 'concerns' ? 'concern' : 'ok',
                $insp && $insp->status === 'in_progress' => 'qa_in_progress',
                $occupied->contains($u->id)              => 'occupied',
                $active && $to->status === 'cleaning_in_progress' => 'cleaning',
                $active && $to->status === 'cleaning_completed'   => 'ready_for_qa',
                $needsCleaning                           => 'needs_cleaning',
                $u->status !== 'available'               => 'offline',
                default                                  => 'ready',
            };

            return [
                'unit_id'       => $u->id,
                'unit_number'   => $u->unit_number,
                'floor'         => $u->floor,
                'unit_type'     => $u->unitType->name,
                'state'         => $state,
                'inspection_id' => $insp?->id,
                'turnover_id'   => $active ? $to->id : null,
                'concern_count' => $insp?->concern_count ?? 0,
                'checkout'      => $out ? $this->timeLabel($out->check_out, $building?->standard_checkout_time) : null,
                'arrival'       => $arr ? $this->timeLabel($arr->check_in, $building?->standard_checkin_time) : null,
            ];
        })->all();
    }

    private function timeLabel($date, $time): array
    {
        return [
            'date' => Carbon::parse($date)->toISOString(),
            'time' => $time ? Carbon::parse($time)->format('g:i A') : null,
        ];
    }

    private function countStates(array $rows): array
    {
        $by = collect($rows)->countBy('state');

        // Units QC still owes work on today.
        $pending   = ($by['ready_for_qa'] ?? 0) + ($by['qa_in_progress'] ?? 0);
        $inspected = ($by['ok'] ?? 0) + ($by['concern'] ?? 0);

        $concerns = collect($rows)->where('state', 'concern')->sum('concern_count');

        return [
            'inspected'      => $inspected,
            'pending'        => $pending,
            'inspectable'    => $inspected + $pending,
            'concerns'       => $concerns,
            // Readiness breakdown for the landing-card chips.
            'ready_for_qa'   => $by['ready_for_qa'] ?? 0,
            'cleaning'       => $by['cleaning'] ?? 0,
            'needs_cleaning' => $by['needs_cleaning'] ?? 0,
            'qa_in_progress' => $by['qa_in_progress'] ?? 0,
            'guest_ready'    => ($by['ok'] ?? 0) + ($by['ready'] ?? 0),
            'blocked'        => ($by['blocked'] ?? 0) + ($by['offline'] ?? 0),
            'occupied'       => $by['occupied'] ?? 0,
        ];
    }

    /**
     * Every failed checklist item in a round — the report management reviews.
     * Sourced from both unit inspections and the property sections, newest first.
     */
    private function roundConcerns(InspectionRound $round): array
    {
        $units    = $round->unitInspections()->with('unit:id,unit_number')->get()->keyBy('id');
        $sections = $round->sectionInspections()->get()->keyBy('id');

        if ($units->isEmpty() && $sections->isEmpty()) {
            return [];
        }

        return $this->failedItemsQuery($units->keys(), $sections->keys())
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($r) use ($units, $sections) {
                if ($r->inspectable_type === UnitInspection::class) {
                    $u = $units->get($r->inspectable_id);
                    $source = 'Unit '.($u?->unit?->unit_number ?? '—');
                } else {
                    $sec = $sections->get($r->inspectable_id);
                    $source = ($sec?->section === 'common') ? 'Guest Common Spaces' : 'Outdoor Space';
                }

                return [
                    'source' => $source,
                    'label'  => $r->item_label,
                    'note'   => $r->note,
                    'photos' => collect($r->photos ?? [])->map(fn ($p) => Storage::url($p))->values(),
                ];
            })->all();
    }

    /** Failed checklist items across the given unit- and section-inspection ids. */
    private function failedItemsQuery($unitIds, $sectionIds)
    {
        return InspectionItemResult::where('result', 'fail')
            ->where(function ($q) use ($unitIds, $sectionIds) {
                $q->where(fn ($w) => $w->where('inspectable_type', UnitInspection::class)->whereIn('inspectable_id', $unitIds))
                    ->orWhere(fn ($w) => $w->where('inspectable_type', RoundSectionInspection::class)->whereIn('inspectable_id', $sectionIds));
            });
    }

    /** The two property-section cards for a round, ensuring both rows exist. */
    private function sectionCards(InspectionRound $round): \Illuminate\Support\Collection
    {
        return collect(self::PROPERTY_SECTIONS)->map(function ($key) use ($round) {
            $sec = RoundSectionInspection::firstOrCreate(
                ['inspection_round_id' => $round->id, 'section' => $key],
                ['building_id' => $round->building_id, 'status' => 'pending']
            );

            $answered = $sec->itemResults()->whereNotNull('result')->count();
            $total    = $sec->itemResults()->count() ?: $this->checklist->sectionPlan($sec)->count();

            return [
                'id'       => $sec->id,
                'section'  => $key,
                'title'    => $key === 'common' ? 'Guest Common Spaces' : 'Outdoor Space',
                'status'   => $sec->status,
                'result'   => $sec->result,
                'answered' => $answered,
                'total'    => $total,
            ];
        });
    }
}

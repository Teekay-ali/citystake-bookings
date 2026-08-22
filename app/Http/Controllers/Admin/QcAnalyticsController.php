<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChecklistItem;
use App\Models\InspectionItemResult;
use App\Models\UnitInspection;
use App\Models\UnitTurnover;
use App\Traits\ScopedByBuilding;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QcAnalyticsController extends Controller
{
    use ScopedByBuilding;

    private const RANGES = [7, 30, 90];

    private const ISSUE_LABELS = [
        'plumbing' => 'Plumbing', 'electrical' => 'Electrical', 'cleanliness' => 'Cleanliness',
        'appliance' => 'Appliance', 'furniture' => 'Furniture', 'safety' => 'Safety', 'general' => 'General',
    ];

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('view-inspections'), 403);

        $days  = in_array((int) $request->input('range'), self::RANGES, true) ? (int) $request->input('range') : 30;
        $start = Carbon::today()->subDays($days - 1)->startOfDay();
        $scoped = $this->scopedBuildingIds();

        // Completed turnover cycles in the window (have a ready_at).
        $turnovers = UnitTurnover::whereIn('building_id', $scoped)
            ->whereNotNull('ready_at')->where('ready_at', '>=', $start)->get();

        // Completed inspections in the window.
        $inspections = UnitInspection::whereIn('building_id', $scoped)
            ->where('status', 'completed')->where('completed_at', '>=', $start)->get(['id', 'score']);

        return Inertia::render('Admin/QcAnalytics/Index', [
            'range'  => $days,
            'ranges' => self::RANGES,
            'stats'  => $this->stats($turnovers, $inspections),
            'stages' => $this->stageAverages($turnovers),
            'trend'  => $this->turnaroundTrend($turnovers, $start, $days),
            'topIssues'    => $this->topIssues($inspections->pluck('id')),
            'byCategory'   => $this->issuesByCategory($inspections->pluck('id')),
        ]);
    }

    private function stats($turnovers, $inspections): array
    {
        $totals = $turnovers->map(fn ($t) => $t->durations()['total'])->filter();
        $avgScore = $inspections->whereNotNull('score')->avg('score');

        return [
            'turnovers'      => $turnovers->count(),
            'inspections'    => $inspections->count(),
            'avg_turnaround' => $totals->isNotEmpty() ? (int) round($totals->avg()) : null, // minutes
            'avg_score'      => $avgScore !== null ? (int) round($avgScore) : null,
        ];
    }

    /** Average minutes for each hand-off stage. */
    private function stageAverages($turnovers): array
    {
        $avg = function (string $key) use ($turnovers) {
            $vals = $turnovers->map(fn ($t) => $t->durations()[$key])->filter();
            return $vals->isNotEmpty() ? (int) round($vals->avg()) : 0;
        };

        return [
            ['label' => 'Checkout → Cleaned', 'minutes' => $avg('checkout_to_cleaned')],
            ['label' => 'Cleaned → QA start', 'minutes' => $avg('cleaned_to_qa')],
            ['label' => 'QA → Ready',         'minutes' => $avg('qa_to_ready')],
        ];
    }

    /** Average total turnaround (minutes) per day, zero-filled. */
    private function turnaroundTrend($turnovers, Carbon $start, int $days): array
    {
        $byDay = $turnovers->groupBy(fn ($t) => $t->ready_at->toDateString());

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $rows = $byDay->get($date->toDateString());
            $avg  = $rows ? $rows->map(fn ($t) => $t->durations()['total'])->filter()->avg() : null;
            $out[] = ['date' => $date->format('d M'), 'minutes' => $avg !== null ? (int) round($avg) : 0];
        }

        return $out;
    }

    /** Top recurring failed checklist items. */
    private function topIssues($inspectionIds): array
    {
        if ($inspectionIds->isEmpty()) {
            return [];
        }

        return InspectionItemResult::where('result', 'fail')
            ->where('inspectable_type', UnitInspection::class)
            ->whereIn('inspectable_id', $inspectionIds)
            ->selectRaw('item_label, COUNT(*) as c')
            ->groupBy('item_label')->orderByDesc('c')->limit(8)->get()
            ->map(fn ($r) => ['label' => $r->item_label, 'count' => (int) $r->c])->all();
    }

    /** Failed items rolled up by issue category (plumbing, electrical, …). */
    private function issuesByCategory($inspectionIds): array
    {
        if ($inspectionIds->isEmpty()) {
            return [];
        }

        $catByKey = ChecklistItem::pluck('issue_category', 'key');

        $counts = InspectionItemResult::where('result', 'fail')
            ->where('inspectable_type', UnitInspection::class)
            ->whereIn('inspectable_id', $inspectionIds)
            ->get(['item_key'])
            ->groupBy(fn ($r) => $catByKey[$r->item_key] ?? 'general')
            ->map->count();

        return $counts->sortDesc()->map(fn ($count, $cat) => [
            'label' => self::ISSUE_LABELS[$cat] ?? ucfirst($cat),
            'count' => $count,
        ])->values()->all();
    }
}

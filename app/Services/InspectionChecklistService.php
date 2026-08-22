<?php

namespace App\Services;

use App\Models\ChecklistItem;
use App\Models\RoundSectionInspection;
use App\Models\UnitInspection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Owns the inspection checklist: expanding the DB template (multiplying bedroom
 * items per bedroom), seeding pending answer rows, grouping them for the UI, and
 * deriving pass/fail scores. Shared by per-unit and per-property inspections.
 */
class InspectionChecklistService
{
    private const SECTION_TITLES = [
        'living_room' => 'Living Room',
        'kitchen'     => 'Kitchen',
        'bedroom'     => 'Bedroom',
        'common'      => 'Guest Common Spaces',
        'outdoor'     => 'Outdoor Space',
    ];

    // ── Template plans ──────────────────────────────────────────────

    /** The rows a unit should answer — bedroom items multiplied per bedroom. */
    public function unitPlan(UnitInspection $inspection): Collection
    {
        $bedrooms = max(1, $inspection->unit?->unitType?->bedroomCount() ?? 1);

        $plan = collect();
        foreach (ChecklistItem::active()->forScope('unit')->orderBy('sort_order')->get() as $item) {
            if ($item->repeats_per_bedroom) {
                for ($i = 1; $i <= $bedrooms; $i++) {
                    $plan->push($this->planRow($item, $i));
                }
            } else {
                $plan->push($this->planRow($item, null));
            }
        }

        return $plan;
    }

    /** The rows a property section (common | outdoor) should answer. */
    public function sectionPlan(RoundSectionInspection $section): Collection
    {
        return ChecklistItem::active()->forScope('property')
            ->where('category', $section->section)
            ->orderBy('sort_order')->get()
            ->map(fn ($item) => $this->planRow($item, null));
    }

    private function planRow(ChecklistItem $item, ?int $bedroomIndex): array
    {
        return [
            'item_key'      => $item->key,
            'item_label'    => $item->label,
            'section'       => $item->section,
            'category'      => $item->category,
            'bedroom_index' => $bedroomIndex,
        ];
    }

    // ── Seeding ─────────────────────────────────────────────────────

    /** Create any missing pending answer rows for an inspectable. Idempotent. */
    public function seed(Model $inspectable, Collection $plan): void
    {
        $existing = $inspectable->itemResults()->get()
            ->keyBy(fn ($r) => $this->rowKey($r->item_key, $r->bedroom_index));

        foreach ($plan as $row) {
            if ($existing->has($this->rowKey($row['item_key'], $row['bedroom_index']))) {
                continue;
            }
            $inspectable->itemResults()->create([
                'item_key'      => $row['item_key'],
                'item_label'    => $row['item_label'],
                'section'       => $row['section'],
                'category'      => $row['category'],
                'bedroom_index' => $row['bedroom_index'],
                'result'        => null,
            ]);
        }
    }

    private function rowKey(string $itemKey, ?int $bedroomIndex): string
    {
        return $itemKey.'|'.($bedroomIndex ?? '');
    }

    // ── Presentation ────────────────────────────────────────────────

    /** Answer rows grouped for the UI: Living Room, Kitchen, Bedroom 1..N, etc. */
    public function grouped(Model $inspectable): array
    {
        $sortMap  = ChecklistItem::pluck('sort_order', 'key');
        $photoReq = ChecklistItem::pluck('requires_photo_on_fail', 'key');

        $results = $inspectable->itemResults()->get()
            ->sortBy(fn ($r) => sprintf('%03d%02d', $sortMap[$r->item_key] ?? 999, $r->bedroom_index ?? 0))
            ->values();

        $groups = [];
        foreach ($results as $r) {
            $groupKey = $r->section.($r->bedroom_index ? '_'.$r->bedroom_index : '');
            if (! isset($groups[$groupKey])) {
                $title = self::SECTION_TITLES[$r->section] ?? ucfirst($r->section);
                if ($r->bedroom_index) {
                    $title .= ' '.$r->bedroom_index;
                }
                $groups[$groupKey] = ['key' => $groupKey, 'title' => $title, 'items' => []];
            }
            $groups[$groupKey]['items'][] = [
                'id'                     => $r->id,
                'item_key'               => $r->item_key,
                'label'                  => $r->item_label,
                'result'                 => $r->result,
                'note'                   => $r->note,
                'requires_photo_on_fail' => (bool) ($photoReq[$r->item_key] ?? true),
                'photos'                 => collect($r->photos ?? [])
                    ->map(fn ($p) => ['path' => $p, 'url' => Storage::url($p)])->values(),
            ];
        }

        return array_values($groups);
    }

    /** [answered, total] for a progress meter. */
    public function progress(Model $inspectable): array
    {
        $total    = $inspectable->itemResults()->count();
        $answered = $inspectable->itemResults()->whereNotNull('result')->count();

        return ['answered' => $answered, 'total' => $total];
    }

    // ── Scoring & completion ────────────────────────────────────────

    /** Derived section score: fail if any item failed, otherwise pass. */
    public function deriveResult(Model $inspectable): string
    {
        return $inspectable->itemResults()->where('result', 'fail')->exists() ? 'fail' : 'pass';
    }

    /**
     * Why an inspectable can't be completed yet, or null when it's ready.
     * Every item must be answered; every Fail needs a note (+ photo when the
     * item requires one).
     */
    public function completionError(Model $inspectable): ?string
    {
        $results = $inspectable->itemResults()->get();

        if ($results->isEmpty()) {
            return 'There are no checklist items to inspect.';
        }
        if ($results->contains(fn ($r) => $r->result === null)) {
            return 'Answer every item (Pass, Fail or N/A) before completing.';
        }

        $photoReq = ChecklistItem::pluck('requires_photo_on_fail', 'key');
        foreach ($results as $r) {
            if ($r->result !== 'fail') {
                continue;
            }
            if (blank($r->note)) {
                return 'Every failed item needs a note explaining the issue.';
            }
            if (($photoReq[$r->item_key] ?? true) && empty($r->photos)) {
                return 'Every failed item needs at least one photo as evidence.';
            }
        }

        return null;
    }
}

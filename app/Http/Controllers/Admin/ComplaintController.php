<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Complaint::with(['building', 'submittedBy'])
            ->latest();

        // Building scope
        if (!$user->hasGlobalAccess()) {
            $query->whereIn('building_id', $user->accessibleBuildingIds());
        }

        if ($request->building_id) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->severity) {
            $query->where('severity', $request->severity);
        }

        $complaints = $query->paginate(20)->withQueryString();

        $buildings = Building::when(!$user->hasGlobalAccess(), function ($q) use ($user) {
            $q->whereIn('id', $user->accessibleBuildingIds());
        })->get(['id', 'name']);

        return Inertia::render('Admin/Complaints/Index', [
            'complaints' => $complaints,
            'buildings'  => $buildings,
            'filters'    => $request->only(['building_id', 'status', 'severity']),
            'counts'     => [
                'open'        => Complaint::where('status', 'open')->count(),
                'in_progress' => Complaint::where('status', 'in_progress')->count(),
            ],
        ]);
    }

    public function create()
    {
        $user = auth()->user();

        $buildings = Building::when(!$user->hasGlobalAccess(), function ($q) use ($user) {
            $q->whereIn('id', $user->accessibleBuildingIds());
        })->where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Admin/Complaints/Create', [
            'buildings' => $buildings,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'title'       => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'location'    => 'nullable|string|max:255',
            'severity'    => 'required|in:low,medium,high,urgent',
            'photos'      => 'nullable|array|max:5',
            'photos.*'    => 'image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB each
        ]);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('complaints', 'public');
                $photoPaths[] = $path;
            }
        }

        Complaint::create([
            'building_id'  => $validated['building_id'],
            'submitted_by' => auth()->id(),
            'title'        => $validated['title'],
            'description'  => $validated['description'],
            'location'     => $validated['location'] ?? null,
            'severity'     => $validated['severity'],
            'photos'       => $photoPaths ?: null,
        ]);

        return redirect()->route('manage.complaints.index')
            ->with('success', 'Complaint submitted successfully.');
    }

    public function show(Complaint $complaint)
    {
        $this->authorizeBuilding($complaint);

        $complaint->load(['building', 'submittedBy', 'resolvedBy']);

        return Inertia::render('Admin/Complaints/Show', [
            'complaint' => array_merge($complaint->toArray(), [
                'photo_urls' => collect($complaint->photos ?? [])
                    ->map(fn($p) => Storage::url($p))
                    ->values(),
            ]),
        ]);
    }

    public function resolve(Request $request, Complaint $complaint)
    {
        abort_unless(auth()->user()->can('manage-complaints'), 403);

        $this->authorizeBuilding($complaint);

        $validated = $request->validate([
            'resolution_notes' => 'required|string|max:1000',
            'status'           => 'required|in:in_progress,resolved',
        ]);

        $complaint->update([
            'status'           => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'],
            'resolved_by'      => auth()->id(),
            'resolved_at'      => $validated['status'] === 'resolved' ? now() : null,
        ]);

        return back()->with('success', 'Complaint updated successfully.');
    }

    public function destroy(Complaint $complaint)
    {
        $this->authorizeBuilding($complaint);

        // Delete photos from storage
        foreach ($complaint->photos ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $complaint->delete();

        return redirect()->route('manage.complaints.index')
            ->with('success', 'Complaint deleted.');
    }

    private function authorizeBuilding(Complaint $complaint): void
    {
        $user = auth()->user();
        if (!$user->hasGlobalAccess() &&
            !in_array($complaint->building_id, $user->accessibleBuildingIds())) {
            abort(403);
        }
    }
}

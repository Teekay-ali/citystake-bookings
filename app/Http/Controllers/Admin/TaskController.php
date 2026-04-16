<?php

namespace App\Http\Controllers\Admin;

use App\Notifications\TaskAssignedNotification;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Task;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Traits\ScopedByBuilding;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    use ScopedByBuilding;

    public function index(Request $request)
    {
        $user        = auth()->user();
        $buildingIds = $user->hasGlobalAccess()
            ? Building::pluck('id')->toArray()
            : $user->accessibleBuildingIds();

        $buildingId = $request->input('building_id');
        $scopedIds  = ($buildingId && in_array((int)$buildingId, $buildingIds))
            ? [(int)$buildingId] : $buildingIds;

        $query = Task::with(['assignedTo', 'createdBy', 'building', 'subtasks'])
            ->whereIn('building_id', $scopedIds);

        // My tasks vs all tasks
        if ($request->view === 'mine') {
            $query->where('assigned_to', $user->id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Sort overdue first, then by due date
        $query->orderByRaw("
            CASE
                WHEN status NOT IN ('completed','cancelled')
                     AND due_date IS NOT NULL
                     AND due_date < CURDATE() THEN 0
                ELSE 1
            END,
            CASE WHEN due_date IS NULL THEN 1 ELSE 0 END,
            due_date ASC,
            created_at DESC
        ");

        $tasks = $query->paginate(20)->withQueryString();

        $buildings = $this->accessibleBuildings()->get(['id', 'name']);

        $staffMembers = User::whereHas('buildings', function ($q) use ($scopedIds) {
            $q->whereIn('buildings.id', $scopedIds);
        })->where(function ($q) {
            $q->where('is_staff', true)->orWhere('is_admin', true);
        })->orderBy('name')->get(['id', 'name']);

        $counts = [
            'pending'     => Task::whereIn('building_id', $scopedIds)->where('status', 'pending')->count(),
            'in_progress' => Task::whereIn('building_id', $scopedIds)->where('status', 'in_progress')->count(),
            'overdue'     => Task::whereIn('building_id', $scopedIds)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now())
                ->count(),
            'mine'        => Task::whereIn('building_id', $scopedIds)
                ->where('assigned_to', $user->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count(),
        ];

        return Inertia::render('Admin/Tasks/Index', [
            'tasks'       => $tasks,
            'buildings'   => $buildings,
            'staffMembers'=> $staffMembers,
            'counts'      => $counts,
            'filters'     => $request->only([
                'building_id', 'status', 'priority', 'assigned_to', 'view'
            ]),
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->can('manage-tasks'), 403);

        $user = auth()->user();

        $buildings = $this->accessibleBuildings()->get(['id', 'name']);

        $buildingIds = $user->hasGlobalAccess()
            ? $buildings->pluck('id')->toArray()
            : $user->accessibleBuildingIds();

        $staffMembers = User::whereHas('buildings', function ($q) use ($buildingIds) {
            $q->whereIn('buildings.id', $buildingIds);
        })->where(function ($q) {
            $q->where('is_staff', true)->orWhere('is_admin', true);
        })->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Tasks/Create', [
            'buildings'    => $buildings,
            'staffMembers' => $staffMembers,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage-tasks'), 403);

        $validated = $request->validate([
            'building_id'  => 'required|exists:buildings,id',
            'assigned_to'  => 'nullable|exists:users,id',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:2000',
            'priority'     => 'required|in:low,medium,high,urgent',
            'due_date'     => 'nullable|date',
            'subtasks'     => 'nullable|array',
            'subtasks.*.title' => 'required|string|max:255',
        ]);

        $task = Task::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        if ($task->assigned_to) {
            $assignee = \App\Models\User::find($task->assigned_to);
            // Don't notify yourself
            if ($assignee && $assignee->id !== auth()->id()) {
                $assignee->notify(new TaskAssignedNotification($task));
            }
        }

        foreach ($validated['subtasks'] ?? [] as $subtask) {
            $task->subtasks()->create(['title' => $subtask['title']]);
        }

        return redirect()->route('manage.tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $this->authorizeBuilding($task);

        $task->load(['assignedTo', 'createdBy', 'building', 'subtasks']);

        return Inertia::render('Admin/Tasks/Show', [
            'task' => array_merge($task->toArray(), [
                'is_overdue'         => $task->isOverdue(),
                'completion_percent' => $task->completionPercent(),
            ]),
        ]);
    }

    public function edit(Task $task)
    {
        abort_unless(auth()->user()->can('manage-tasks'), 403);
        $this->authorizeBuilding($task);

        $task->load(['subtasks', 'assignedTo']);

        $user        = auth()->user();
        $buildings   = $this->accessibleBuildings()->get(['id', 'name']);
        $buildingIds = $user->hasGlobalAccess()
            ? $buildings->pluck('id')->toArray()
            : $user->accessibleBuildingIds();

        $staffMembers = User::whereHas('buildings', function ($q) use ($buildingIds) {
            $q->whereIn('buildings.id', $buildingIds);
        })->where(function ($q) {
            $q->where('is_staff', true)->orWhere('is_admin', true);
        })->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Tasks/Edit', [
            'task'         => $task,
            'buildings'    => $buildings,
            'staffMembers' => $staffMembers,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        abort_unless(auth()->user()->can('manage-tasks'), 403);
        $this->authorizeBuilding($task);

        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'priority'    => 'required|in:low,medium,high,urgent',
            'status'      => 'required|in:pending,in_progress,completed,cancelled',
            'due_date'    => 'nullable|date',
        ]);

        if ($validated['status'] === 'completed' && $task->status !== 'completed') {
            $validated['completed_at'] = now();
        }

        $task->update($validated);

        if ($task->assigned_to) {
            $assignee = \App\Models\User::find($task->assigned_to);
            // Don't notify yourself
            if ($assignee && $assignee->id !== auth()->id()) {
                $assignee->notify(new TaskAssignedNotification($task));
            }
        }

        return back()->with('success', 'Task updated.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $this->authorizeBuilding($task);

        // Assigned user can update status
        if ($task->assigned_to !== auth()->id() &&
            !auth()->user()->can('manage-tasks')) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
            // Auto-complete all subtasks
            $task->subtasks()->update([
                'completed'    => true,
                'completed_at' => now(),
            ]);
        }

        $task->update($validated);

        return back()->with('success', 'Status updated.');
    }

    public function toggleSubtask(Request $request, Task $task, TaskSubtask $subtask)
    {
        $this->authorizeBuilding($task);

        if ($task->assigned_to !== auth()->id() &&
            !auth()->user()->can('manage-tasks')) {
            abort(403);
        }

        $subtask->update([
            'completed'    => !$subtask->completed,
            'completed_at' => !$subtask->completed ? now() : null,
        ]);

        // Auto-mark task in_progress when first subtask is ticked
        if ($subtask->completed && $task->status === 'pending') {
            $task->update(['status' => 'in_progress']);
        }

        // Auto-complete task when all subtasks done
        if ($task->subtasks()->where('completed', false)->doesntExist()) {
            $task->update(['status' => 'completed', 'completed_at' => now()]);
        }

        return back()->with('success', 'Subtask updated.');
    }

    public function destroy(Task $task)
    {
        abort_unless(auth()->user()->can('manage-tasks'), 403);
        $this->authorizeBuilding($task);

        $task->subtasks()->delete();
        $task->delete();

        return redirect()->route('manage.tasks.index')
            ->with('success', 'Task deleted.');
    }

    private function authorizeBuilding(Task $task): void
    {
        $user = auth()->user();
        if (!$user->hasGlobalAccess() &&
            !in_array($task->building_id, $user->accessibleBuildingIds())) {
            abort(403);
        }
    }
}

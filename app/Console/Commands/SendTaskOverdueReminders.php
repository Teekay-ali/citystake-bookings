<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskOverdueNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTaskOverdueReminders extends Command
{
    protected $signature   = 'tasks:send-overdue-reminders';
    protected $description = 'Send reminders for tasks due tomorrow, today, and overdue tasks (max 3 days)';

    public function handle(): void
    {
        $today    = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // ── 1. Warning — due tomorrow ─────────────────────────
        $dueTomorrow = Task::whereNotIn('status', ['completed', 'cancelled'])
            ->whereDate('due_date', $tomorrow)
            ->whereNotNull('assigned_to')
            ->with(['assignedTo', 'createdBy', 'building'])
            ->get();

        foreach ($dueTomorrow as $task) {
            $task->assignedTo?->notify(new TaskOverdueNotification($task, 'warning'));
        }

        $this->info("Warning reminders sent: {$dueTomorrow->count()}");

        // ── 2. Overdue — due today or up to 3 days ago ────────
        $overdue = Task::whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today)
            ->whereDate('due_date', '>=', $today->copy()->subDays(3))
            ->whereNotNull('assigned_to')
            ->with(['assignedTo', 'createdBy', 'building'])
            ->get();

        foreach ($overdue as $task) {
            $daysOverdue = $task->due_date->diffInDays($today);
            $type        = $daysOverdue >= 2 ? 'escalation' : 'overdue';

            // Always notify assigned staff
            $task->assignedTo?->notify(new TaskOverdueNotification($task, $type));

            // Notify creator if different from assignee
            if ($task->created_by !== $task->assigned_to) {
                $task->createdBy?->notify(new TaskOverdueNotification($task, $type));
            }

            // Escalation — also notify managers of the building
            if ($type === 'escalation') {
                $managers = User::role(['manager', 'super-admin'])
                    ->whereHas('buildings', fn($q) => $q->where('buildings.id', $task->building_id))
                    ->get();

                foreach ($managers as $manager) {
                    // Don't double-notify if manager is the creator or assignee
                    if ($manager->id !== $task->assigned_to && $manager->id !== $task->created_by) {
                        $manager->notify(new TaskOverdueNotification($task, 'escalation'));
                    }
                }
            }
        }

        $this->info("Overdue reminders sent: {$overdue->count()}");
    }
}

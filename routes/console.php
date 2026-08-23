<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Console\Commands\SendTaskOverdueReminders;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tasks:send-overdue-reminders')->dailyAt('08:00');

// Send booking reminders daily at 9 AM
Schedule::command('bookings:send-reminders')->dailyAt('09:00');

Schedule::command('bookings:send-checkout-reminders')->dailyAt('09:30');

// Nudge staff about un-checked-in arrivals, hourly from 2 PM to 8 PM
Schedule::command('bookings:remind-checkins')->hourlyAt(0)->between('14:00', '20:00');

// Nudge staff about due/overdue weekly-plan payments, daily at 9:15 AM
Schedule::command('bookings:remind-installments')->dailyAt('09:15');

// Prune read notifications older than 30 days so the table doesn't grow unbounded
Schedule::command('notifications:prune')->dailyAt('03:00');

// Prune page-visit records older than 90 days
Schedule::command('page-visits:prune')->dailyAt('03:10');

// Close inspection rounds left open from a previous day
Schedule::command('inspections:close-stale-rounds')->dailyAt('02:00');

// Prune orphaned inspection photo files
Schedule::command('inspections:prune-photos')->weeklyOn(1, '03:30');

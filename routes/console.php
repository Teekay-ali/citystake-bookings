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

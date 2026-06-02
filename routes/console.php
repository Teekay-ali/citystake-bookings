<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Console\Commands\SendTaskOverdueReminders;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bookings:expire-unpaid')->everyFifteenMinutes();

Schedule::command('tasks:send-overdue-reminders')->dailyAt('08:00');

// Send booking reminders daily at 9 AM
Schedule::command('bookings:send-reminders')->dailyAt('09:00');

Schedule::command('bookings:send-checkout-reminders')->dailyAt('09:30');

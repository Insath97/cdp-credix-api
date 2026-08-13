<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('installments:send-due-reminders')->daily();

Schedule::command('loans:mark-overdue')->dailyAt('00:30')->withoutOverlapping();
Schedule::command('installments:send-overdue-sms')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('recovery:escalate-internal')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('recovery:escalate-external')->dailyAt('02:00')->withoutOverlapping();

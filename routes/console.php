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

// Last in the nightly chain on purpose: loans:mark-overdue has already stamped
// today's newly overdue rows by now, so the scores this writes reflect the same
// state the recovery screens do. Only live loans are walked -- the payment,
// reversal and installment-edit paths rescore inline, so a closed loan's score
// is already final.
Schedule::command('credit-score:recompute')->dailyAt('02:30')->withoutOverlapping();

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sinkronkan status terlambat & kirim email pengingat jatuh tempo setiap hari pukul 07:00
Schedule::command('loans:send-due-reminders')->dailyAt('07:00');

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat otomatis harian untuk berkas fisik yang melewati batas jatuh tempo (dijalankan pukul 08:00 WIB)
Schedule::command('loans:send-overdue-reminders')->dailyAt('08:00');

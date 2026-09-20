<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Kirim ringkasan stok roti + laporan PDF ke grup WhatsApp setiap jam 00:00
// WIB. Laravel Cloud otomatis "membangunkan" environment untuk menjalankan
// ini walau sedang scale-to-zero, asal toggle Scheduler diaktifkan di
// dashboard (App compute cluster > Scheduler).
Schedule::command('laporan:kirim-harian')
    ->dailyAt('00:00')
    ->timezone('Asia/Jakarta')
    ->onOneServer();

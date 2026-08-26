<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

// Nightly database backup (gzip, pruned to the newest 14 copies).
Schedule::command('app:backup-db --keep=14')
    ->dailyAt('03:00')
    ->environments(['production', 'local'])
    ->onSuccess(function () {
        Log::info('Nightly DB backup succeeded.');
    })
    ->onFailure(function () {
        Log::error('Nightly DB backup FAILED.');
    });

<?php

use AbraNl\BackupMonitor\Http\Controllers\BackupMonitorController;
use Illuminate\Support\Facades\Route;

Route::prefix('backup-monitor')->name('backup-monitor.')->group(function () {
    Route::get('/', [BackupMonitorController::class, 'index'])->name('index');
    Route::post('/trigger', [BackupMonitorController::class, 'trigger'])->name('trigger');
});

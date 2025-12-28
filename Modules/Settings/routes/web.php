<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\CompanySettingController;

Route::prefix('settings')->name('settings.')->middleware(['web', 'auth', 'verified'])->group(function () {
    // Company Settings
    Route::get('/company', [CompanySettingController::class, 'index'])->name('company.index');
    Route::put('/company', [CompanySettingController::class, 'update'])->name('company.update');
});

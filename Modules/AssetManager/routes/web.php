<?php

use Illuminate\Support\Facades\Route;
use Modules\AssetManager\Http\Controllers\AssetController;
use Modules\AssetManager\Http\Controllers\EmployeeAssetController;

Route::group(['middleware' => ['auth'], 'prefix' => 'assetmanager', 'as' => 'assetmanager.'], function () {
  // Asset management routes
  Route::resource('assets', AssetController::class);

  // Asset assignment routes
  Route::prefix('assets')->as('assets.')->group(function () {
    Route::post('/assign', [AssetController::class, 'assign'])->name('assign');
    Route::post('/unassign', [AssetController::class, 'unassign'])->name('unassign');
  });

  // Employee asset assignment routes
  Route::prefix('employee-assets')->as('employee-assets.')->group(function () {
    Route::post('/{employee}/assign', [EmployeeAssetController::class, 'assign'])->name('assign');
    Route::post('/{employee}/unassign', [EmployeeAssetController::class, 'unassign'])->name('unassign');
  });
});

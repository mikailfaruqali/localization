<?php

use Illuminate\Support\Facades\Route;
use Snawbar\Localization\Http\Controllers\LocalizationController;
use Snawbar\Localization\Http\Controllers\OverrideController;

Route::prefix(config()->string('snawbar-localization.route', 'localization'))
    ->middleware(config()->array('snawbar-localization.middleware', ['web']))
    ->name('snawbar.')
    ->group(function () {
        Route::controller(LocalizationController::class)->group(function () {
            Route::get('view', 'index')->name('localization.view');
            Route::get('compare', 'compare')->name('localization.compare');
            Route::post('update', 'update')->name('localization.update');
        });

        Route::controller(OverrideController::class)->group(function () {
            Route::get('/overrides', 'index')->name('overrides.index');
            Route::get('/overrides/search', 'search')->name('overrides.search');
            Route::post('/overrides/store', 'store')->name('overrides.store');
            Route::post('/overrides/update', 'update')->name('overrides.update');
            Route::delete('/overrides/delete', 'destroy')->name('overrides.destroy');
        });
    });

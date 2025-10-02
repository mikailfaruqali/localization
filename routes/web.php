<?php

use Illuminate\Support\Facades\Route;
use Snawbar\Localization\Controllers\LocalizationController;
use Snawbar\Localization\Controllers\OverrideController;

Route::prefix(config('snawbar-localization.route', 'localization'))
    ->middleware(config('snawbar-localization.middleware', ['web']))
    ->name('snawbar.')
    ->group(function () {
        Route::controller(LocalizationController::class)->name('localization.')->group(function () {
            Route::get('view', 'index')->name('view');
            Route::get('compare', 'compare')->name('compare');
            Route::post('update', 'update')->name('update');
            Route::get('download-all', 'downloadLang')->name('downloadAll');
        });

        Route::prefix('overrides')->controller(OverrideController::class)->name('overrides.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/search', 'search')->name('search');
            Route::post('/store', 'store')->name('store');
            Route::post('/update', 'update')->name('update');
            Route::delete('/delete', 'destroy')->name('destroy');
            Route::get('/original-values', 'getOriginalValues')->name('originalValues');
        });
    });

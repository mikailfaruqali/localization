<?php

use Illuminate\Support\Facades\Route;
use Snawbar\Localization\Http\Controllers\LocalizationController;

Route::prefix(config()->string('snawbar-localization.route', 'localization'))
    ->middleware(config()->array('snawbar-localization.middleware', ['web']))
    ->controller(LocalizationController::class)
    ->name('snawbar.')
    ->group(function () {
        Route::get('view', 'index')->name('localization.view');
        Route::get('compare', 'compare')->name('localization.compare');
        Route::post('update', 'update')->name('localization.update');
    });

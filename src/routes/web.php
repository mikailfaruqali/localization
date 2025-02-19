<?php

use Illuminate\Support\Facades\Route;
use Snawbar\Localization\Http\Controllers\LocalizationController;

Route::prefix('localization')
    ->middleware(config('snawbar-localization.middleware'))
    ->controller(LocalizationController::class)
    ->group(function () {
        Route::get('view', 'index')->name('localization.view');
        Route::post('update', 'update')->name('localization.update');
    });

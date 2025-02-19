<?php

namespace Snawbar\Localization;

use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/views', 'localization');
    }
}

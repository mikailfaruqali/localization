<?php

namespace Snawbar\Localization;

use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->publishAssets();
    }

    private function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    private function registerViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'snawbar-localization');
    }

    private function publishAssets()
    {
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
            __DIR__ . '/../resources/assets' => public_path('vendor/snawbar-localization'),
            __DIR__ . '/../config/localization.php' => config_path('snawbar-localization.php'),
        ], 'snawbar-localization');
    }
}

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
        if (! $this->app->runningInConsole()) {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        }
    }

    private function registerViews()
    {
        if (! $this->app->runningInConsole()) {
            $this->loadViewsFrom(__DIR__ . '/../resources/views', 'snawbar-localization');
        }
    }

    private function publishAssets()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/assets' => public_path('vendor/snawbar-localization'),
            ], 'snawbar-localization-assets');

            $this->publishes([
                __DIR__ . '/../config/localization.php' => config_path('snawbar-localization.php'),
            ], 'snawbar-localization-config');
        }
    }
}

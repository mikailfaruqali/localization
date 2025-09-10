<?php

namespace Snawbar\Localization\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\Translator;

class OverrideTranslations
{
    public function handle($request, Closure $next)
    {
        $overrides = DB::table('override_translations')
            ->where('locale', app()->getLocale())
            ->pluck('value', 'key')
            ->toArray();

        if (filled($overrides)) {
            app(Translator::class)->addLines($overrides, app()->getLocale(), '*');
        }

        return $next($request);
    }
}

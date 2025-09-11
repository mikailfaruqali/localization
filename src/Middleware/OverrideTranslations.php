<?php

namespace Snawbar\Localization\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\Translator;

class OverrideTranslations
{
    public static function clearCache(string $locale): void
    {
        Cache::forget(sprintf('override_translations.%s', $locale));
    }

    public function handle($request, Closure $next)
    {
        $locale = app()->getLocale();
        $cacheKey = sprintf('override_translations.%s', $locale);

        $overrides = Cache::rememberForever($cacheKey, fn () => DB::table('override_translations')
            ->where('locale', $locale)
            ->pluck('value', 'key')
            ->toArray());

        if (filled($overrides)) {
            app(Translator::class)->addLines($overrides, $locale, '*');
        }

        return $next($request);
    }
}

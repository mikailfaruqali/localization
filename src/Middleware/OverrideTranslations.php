<?php

namespace Snawbar\Localization\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\Translator;
use Symfony\Component\HttpFoundation\Response;

final class OverrideTranslations
{
    private const CACHE_PATTERN = 'override_translations.%s';

    private const GROUP_SEPARATOR = '.';

    private const GLOBAL_NAMESPACE = '*';

    public static function clearCache(): void
    {
        DB::table('override_translations')
            ->pluck('locale')
            ->unique()
            ->each(fn ($locale) => Cache::forget($this->cacheKey($locale)));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $locale = app()->getLocale();
        $overrides = $this->getOverrides($locale);

        if (blank($overrides)) {
            return $next($request);
        }

        $this->applyOverrides($overrides, $locale);

        return $next($request);
    }

    private function cacheKey(string $locale): string
    {
        return sprintf(self::CACHE_PATTERN, $locale);
    }

    private function getOverrides(string $locale): array
    {
        return Cache::rememberForever(
            $this->cacheKey($locale),
            fn () => DB::table('override_translations')
                ->where('locale', $locale)
                ->pluck('value', 'key')
                ->toArray()
        );
    }

    private function applyOverrides(array $overrides, string $locale): void
    {
        $translator = app(Translator::class);

        foreach ($this->extractGroups($overrides) as $group) {
            $translator->load(self::GLOBAL_NAMESPACE, $group, $locale);
        }

        $translator->addLines($overrides, $locale, self::GLOBAL_NAMESPACE);
    }

    private function extractGroups(array $overrides): array
    {
        return array_values(array_unique(
            array_map(fn ($key) => strtok($key, self::GROUP_SEPARATOR), array_keys($overrides))
        ));
    }
}

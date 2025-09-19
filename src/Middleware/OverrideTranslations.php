<?php

declare(strict_types=1);

namespace Snawbar\Localization\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\Translator;
use Symfony\Component\HttpFoundation\Response;

final class OverrideTranslations
{
    private const CACHE_PREFIX = 'override_translations';

    private const GLOBAL_NAMESPACE = '*';

    private const KEY_SEPARATOR = '.';

    public static function clearCache(string $locale): void
    {
        Cache::forget(self::getCacheKey($locale));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $locale = app()->getLocale();
        $overrides = $this->getOverrides($locale);

        if ($overrides !== []) {
            $this->applyOverrides($overrides, $locale);
        }

        return $next($request);
    }

    private static function getCacheKey(string $locale): string
    {
        return sprintf('%s.%s', self::CACHE_PREFIX, $locale);
    }

    private function getOverrides(string $locale): array
    {
        return Cache::rememberForever(
            self::getCacheKey($locale),
            fn (): array => $this->fetchOverridesFromDatabase($locale)
        );
    }

    private function fetchOverridesFromDatabase(string $locale): array
    {
        return DB::table('override_translations')
            ->where('locale', $locale)
            ->pluck('value', 'key')
            ->toArray();
    }

    private function applyOverrides(array $overrides, string $locale): void
    {
        $translator = app(Translator::class);
        $groupedOverrides = $this->groupOverridesByNamespace($overrides);

        foreach ($groupedOverrides as $namespace => $translations) {
            $translator->addLines($translations, $locale, $namespace);
        }
    }

    private function groupOverridesByNamespace(array $overrides): array
    {
        $grouped = [];

        foreach ($overrides as $key => $value) {
            [$namespace, $translationKey] = $this->parseTranslationKey($key);
            $grouped[$namespace][$translationKey] = $value;
        }

        return $grouped;
    }

    private function parseTranslationKey(string $key): array
    {
        if (! str_contains($key, self::KEY_SEPARATOR)) {
            return [self::GLOBAL_NAMESPACE, $key];
        }

        [$namespace, $translationKey] = explode(self::KEY_SEPARATOR, $key, 2);

        return [$namespace, $translationKey];
    }
}

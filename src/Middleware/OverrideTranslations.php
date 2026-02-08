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
    private const string CACHE_PATTERN = 'override_translations.%s';

    private const string GROUP_SEPARATOR = '.';

    private const string GLOBAL_NAMESPACE = '*';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = app()->getLocale();
        $overrides = $this->getOverrides($locale);

        if (filled($overrides)) {
            $this->applyOverrides($overrides, $locale);
        }

        return $next($request);
    }

    private function getOverrides(string $locale): array
    {
        return Cache::rememberForever(
            sprintf(self::CACHE_PATTERN, $locale),
            fn () => DB::table('override_translations')
                ->where('locale', $locale)
                ->pluck('value', 'key')
                ->toArray()
        );
    }

    private function applyOverrides(array $overrides, string $locale): void
    {
        $translator = resolve(Translator::class);

        foreach ($this->extractGroups($overrides) as $group) {
            $translator->load(self::GLOBAL_NAMESPACE, $group, $locale);
        }

        $translator->addLines($overrides, $locale, self::GLOBAL_NAMESPACE);
    }

    private function extractGroups(array $overrides): array
    {
        return collect($overrides)
            ->keys()
            ->map(fn ($key) => strtok($key, self::GROUP_SEPARATOR))
            ->unique()
            ->values()
            ->all();
    }
}

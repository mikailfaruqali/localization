<?php

namespace Snawbar\Localization\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TranslationCacheManager
{
    private const CACHE_PATTERN = 'override_translations.%s';

    private static array $callbacks = [];

    public static function extend(callable $callback): void
    {
        self::$callbacks[] = $callback;
    }

    public static function clear(): void
    {
        match (filled(self::$callbacks)) {
            TRUE => self::executeCallbacks(),
            FALSE => self::clearDefault(),
        };
    }

    private static function executeCallbacks(): void
    {
        foreach (self::$callbacks as $callback) {
            $callback();
        }
    }

    private static function clearDefault(): void
    {
        DB::table('override_translations')
            ->pluck('locale')
            ->unique()
            ->each(fn ($locale) => Cache::forget(sprintf(self::CACHE_PATTERN, $locale)));
    }
}

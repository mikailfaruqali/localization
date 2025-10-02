<?php

namespace Snawbar\Localization\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Snawbar\Localization\Middleware\OverrideTranslations;

class OverrideController extends Controller
{
    private const MIN_SEARCH_LENGTH = 2;

    private const TABLE = 'override_translations';

    public function index(): View
    {
        return view(
            view: 'snawbar-localization::overrides',
            data: [
                'overrides' => $this->getAllOverrides(),
                'languages' => $this->getLanguages(),
            ]
        );
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input(key: 'query', default: '');

        return match (strlen($query) >= self::MIN_SEARCH_LENGTH) {
            TRUE => response()->json($this->searchTranslations($query)),
            FALSE => response()->json([]),
        };
    }

    public function getOriginalValues(Request $request): JsonResponse
    {
        $request->validate(['key' => 'required|string']);
        $key = $request->input(key: 'key');

        return response()->json([
            'key' => $key,
            'values' => $this->fetchOriginalTranslations($key),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'overrides' => 'required|array',
            'overrides.*.key' => 'required|string',
            'overrides.*.locale' => 'required|string',
            'overrides.*.value' => 'required|string',
        ]);

        $overrides = $validated['overrides'];
        $this->saveOverrides($overrides);
        $this->clearCacheForLocales($this->extractLocales($overrides));

        return response()->json([
            'success' => TRUE,
            'message' => sprintf('Successfully saved %d override(s)', $this->count($overrides)),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate(['value' => 'required|string']);

        $override = $this->findOverride($request->id);
        $this->updateOverride(id: $request->id, value: $request->value);
        $this->clearCache($override?->locale ?? '');

        return response()->json(['success' => 'Successfully updated']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $override = $this->findOverride($request->id);
        $this->deleteOverride($request->id);
        $this->clearCache($override?->locale ?? '');

        return response()->json(['success' => 'Successfully deleted']);
    }

    private function getAllOverrides(): Collection
    {
        return DB::table(table: self::TABLE)->orderByDesc(column: 'id')->get();
    }

    private function getLanguages(): array
    {
        return app(LocalizationController::class)->getLanguages();
    }

    private function searchTranslations(string $query): array
    {
        return collect($this->getTranslationFiles())
            ->flatMap(fn (string $file) => $this->searchInFile($file, $query))
            ->values()
            ->toArray();
    }

    private function getTranslationFiles(): array
    {
        return app(LocalizationController::class)->getFiles();
    }

    private function searchInFile(string $file, string $query): array
    {
        return match ($content = $this->loadFileContent($file)) {
            NULL => [],
            default => $this->filterAndMapResults(
                content: $content,
                prefix: pathinfo(path: $file, flags: PATHINFO_FILENAME),
                query: $query
            ),
        };
    }

    private function filterAndMapResults(array $content, string $prefix, string $query): array
    {
        return collect($content)
            ->filter(fn (string $value, string $key) => $this->matchesQuery($prefix, $key, $value, $query))
            ->map(fn (string $value, string $key) => $this->formatSearchResult($prefix, $key, $value))
            ->toArray();
    }

    private function loadFileContent(string $file): ?array
    {
        $path = $this->buildPath(
            base: config('snawbar-localization.path'),
            locale: config('snawbar-localization.base-locale'),
            file: $file
        );

        $content = @include $path;

        return match (is_array($content)) {
            TRUE => $content,
            FALSE => NULL,
        };
    }

    private function buildPath(string $base, string $locale, string $file): string
    {
        return sprintf('%s/%s/%s', $base, $locale, $file);
    }

    private function matchesQuery(string $prefix, string $key, string $value, string $query): bool
    {
        $fullKey = sprintf('%s.%s', $prefix, $key);

        return str_contains(haystack: strtolower($fullKey), needle: strtolower($query))
            || str_contains(haystack: strtolower($value), needle: strtolower($query));
    }

    private function formatSearchResult(string $prefix, string $key, string $value): array
    {
        $fullKey = sprintf('%s.%s', $prefix, $key);

        return [
            'id' => $fullKey,
            'text' => $fullKey,
            'value' => $value,
        ];
    }

    private function fetchOriginalTranslations(string $key): array
    {
        return collect($this->getLanguages())
            ->mapWithKeys(fn (string $locale) => [$locale => $this->getOriginalTranslation($key, $locale)])
            ->toArray();
    }

    private function getOriginalTranslation(string $key, string $locale): string
    {
        $parts = $this->parseTranslationKey($key);

        return match (TRUE) {
            $parts === NULL => '',
            default => $this->extractTranslationValue($parts, $locale),
        };
    }

    private function extractTranslationValue(array $parts, string $locale): string
    {
        $content = $this->loadLocaleFile(file: $parts['file'], locale: $locale);

        return match ($content) {
            NULL => '',
            default => $this->extractNestedValue($content, $parts['nested_key']),
        };
    }

    private function parseTranslationKey(string $key): ?array
    {
        $parts = explode(separator: '.', string: $key);

        return match (count($parts) >= 2) {
            TRUE => [
                'file' => array_shift($parts),
                'nested_key' => implode(separator: '.', array: $parts),
            ],
            FALSE => NULL,
        };
    }

    private function loadLocaleFile(string $file, string $locale): ?array
    {
        $path = $this->buildLocalePath(
            base: config('snawbar-localization.path'),
            locale: $locale,
            file: $file
        );

        return match (file_exists($path)) {
            FALSE => NULL,
            TRUE => $this->includeFile($path),
        };
    }

    private function buildLocalePath(string $base, string $locale, string $file): string
    {
        return sprintf('%s/%s/%s.php', $base, $locale, $file);
    }

    private function includeFile(string $path): ?array
    {
        $content = @include $path;

        return match (is_array($content)) {
            TRUE => $content,
            FALSE => NULL,
        };
    }

    private function extractNestedValue(array $content, string $nestedKey): string
    {
        $keys = explode(separator: '.', string: $nestedKey);
        $value = $content;

        foreach ($keys as $key) {
            $value = match (isset($value[$key])) {
                TRUE => $value[$key],
                FALSE => NULL,
            };

            if ($value === NULL) {
                return '';
            }
        }

        return match (is_string($value)) {
            TRUE => $value,
            FALSE => '',
        };
    }

    private function saveOverrides(array $overrides): void
    {
        collect($overrides)->each(
            fn (array $override) => DB::table(table: self::TABLE)->updateOrInsert(
                attributes: [
                    'key' => $override['key'],
                    'locale' => $override['locale'],
                ],
                values: ['value' => $override['value']]
            )
        );
    }

    private function extractLocales(array $overrides): array
    {
        return collect($overrides)
            ->pluck('locale')
            ->unique()
            ->toArray();
    }

    private function clearCacheForLocales(array $locales): void
    {
        collect($locales)->each(fn (string $locale) => $this->clearCache($locale));
    }

    private function clearCache(string $locale): void
    {
        OverrideTranslations::clearCache($locale);
    }

    private function findOverride(int $id): ?object
    {
        return DB::table(table: self::TABLE)->find(id: $id);
    }

    private function updateOverride(int $id, string $value): void
    {
        DB::table(table: self::TABLE)->updateOrInsert(
            attributes: ['id' => $id],
            values: ['value' => $value]
        );
    }

    private function deleteOverride(int $id): void
    {
        DB::table(table: self::TABLE)->where(column: 'id', operator: '=', value: $id)->delete();
    }

    private function count(array $items): int
    {
        return count($items);
    }
}

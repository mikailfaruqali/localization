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
        return view('snawbar-localization::overrides', [
            'overrides' => $this->getAllOverrides(),
            'languages' => $this->getLanguages(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query', '');

        if (strlen($query) >= self::MIN_SEARCH_LENGTH) {
            return response()->json($this->searchTranslations($query));
        }

        return response()->json([]);
    }

    public function getOriginalValues(Request $request): JsonResponse
    {
        $request->validate([
            'key' => ['required', 'string'],
        ]);

        $key = $request->input('key');

        return response()->json([
            'key' => $key,
            'values' => $this->fetchOriginalTranslations($key),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'overrides' => ['required', 'array'],
            'overrides.*.key' => ['required', 'string'],
            'overrides.*.locale' => ['required', 'string'],
            'overrides.*.value' => ['required', 'string'],
        ]);

        $this->saveOverrides($request->overrides);
        $this->clearCache();

        return response()->json([
            'success' => TRUE,
            'message' => sprintf('Successfully saved %d override(s)', $this->count($request->overrides)),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'value' => ['required', 'string'],
        ]);

        $this->updateOverride($request->id, $request->value);
        $this->clearCache();

        return response()->json(['success' => 'Successfully updated']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->deleteOverride($request->id);
        $this->clearCache();

        return response()->json(['success' => 'Successfully deleted']);
    }

    private function getAllOverrides(): Collection
    {
        return DB::table(self::TABLE)->orderByDesc('id')->get();
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
        $content = $this->loadFileContent($file);

        if ($content === NULL) {
            return [];
        }

        return $this->filterAndMapResults($content, pathinfo($file, PATHINFO_FILENAME), $query);
    }

    private function filterAndMapResults(array $content, string $prefix, string $query): array
    {
        return collect($content)
            ->filter(fn (string $value, string $key) => $this->matchesQuery($prefix, $key, $value, $query))
            ->map(fn (string $value, string $key) => $this->formatSearchResult($prefix, $key, $value))
            ->all();
    }

    private function loadFileContent(string $file): ?array
    {
        $path = $this->buildPath(config('snawbar-localization.path'), config('snawbar-localization.base-locale'), $file);

        $content = @include $path;

        if (is_array($content)) {
            return $content;
        }

        return NULL;
    }

    private function buildPath(string $base, string $locale, string $file): string
    {
        return sprintf('%s/%s/%s', $base, $locale, $file);
    }

    private function matchesQuery(string $prefix, string $key, string $value, string $query): bool
    {
        return strpos(strtolower(sprintf('%s.%s', $prefix, $key)), strtolower($query)) !== FALSE
            || strpos(strtolower($value), strtolower($query)) !== FALSE;
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
            ->all();
    }

    private function getOriginalTranslation(string $key, string $locale): string
    {
        $parts = $this->parseTranslationKey($key);

        if ($parts === NULL) {
            return '';
        }

        return $this->extractTranslationValue($parts, $locale);
    }

    private function extractTranslationValue(array $parts, string $locale): string
    {
        $content = $this->loadLocaleFile($parts['file'], $locale);

        if ($content === NULL) {
            return '';
        }

        return $this->extractNestedValue($content, $parts['nested_key']);
    }

    private function parseTranslationKey(string $key): ?array
    {
        $parts = explode('.', $key);

        if (count($parts) >= 2) {
            return [
                'file' => array_shift($parts),
                'nested_key' => implode('.', $parts),
            ];
        }

        return NULL;
    }

    private function loadLocaleFile(string $file, string $locale): ?array
    {
        $path = $this->buildLocalePath(config('snawbar-localization.path'), $locale, $file);

        if (! file_exists($path)) {
            return NULL;
        }

        return $this->includeFile($path);
    }

    private function buildLocalePath(string $base, string $locale, string $file): string
    {
        return sprintf('%s/%s/%s.php', $base, $locale, $file);
    }

    private function includeFile(string $path): ?array
    {
        $content = @include $path;

        if (is_array($content)) {
            return $content;
        }

        return NULL;
    }

    private function extractNestedValue(array $content, string $nestedKey): string
    {
        $keys = explode('.', $nestedKey);
        $value = $content;

        foreach ($keys as $key) {
            $value = $value[$key] ?? NULL;

            if ($value === NULL) {
                return '';
            }
        }

        if (is_string($value)) {
            return $value;
        }

        return '';
    }

    private function saveOverrides(array $overrides): void
    {
        DB::table(self::TABLE)->upsert($overrides, ['key', 'locale'], ['value']);
    }

    private function clearCache(): void
    {
        OverrideTranslations::clearCache();
    }

    private function updateOverride(int $id, string $value): void
    {
        DB::table(self::TABLE)->updateOrInsert(['id' => $id], [
            'value' => $value,
        ]);
    }

    private function deleteOverride(int $id): void
    {
        DB::table(self::TABLE)->where('id', '=', $id)->delete();
    }

    private function count(array $items): int
    {
        return count($items);
    }
}

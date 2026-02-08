<?php

namespace Snawbar\Localization\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Snawbar\Localization\Services\TranslationCacheManager;

class OverrideController extends Controller
{
    private const int MIN_SEARCH_LENGTH = 2;

    private const string TABLE = 'override_translations';

    public function __construct(
        private readonly LocalizationController $localizationController
    ) {}

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

        $results = match (strlen((string) $query) >= self::MIN_SEARCH_LENGTH) {
            TRUE => $this->searchTranslations($query),
            FALSE => [],
        };

        return response()->json($results);
    }

    public function getOriginalValues(Request $request): JsonResponse
    {
        $request->validate([
            'key' => ['required', 'string'],
        ]);

        return response()->json([
            'key' => $request->input('key'),
            'values' => $this->fetchOriginalTranslations($request->input('key')),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'overrides' => ['required', 'array'],
            'overrides.*.key' => ['required', 'string'],
            'overrides.*.locale' => ['required', 'string'],
            'overrides.*.value' => ['required', 'string'],
        ]);

        $this->saveOverrides($validated['overrides']);
        $this->clearCache();

        return response()->json([
            'message' => sprintf('Successfully saved %d override(s)', count($validated['overrides'])),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'value' => ['required', 'string'],
        ]);

        $this->updateOverride($validated['id'], $validated['value']);
        $this->clearCache();

        return response()->json(['message' => 'Successfully updated']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $this->deleteOverride($validated['id']);
        $this->clearCache();

        return response()->json(['message' => 'Successfully deleted']);
    }

    private function getAllOverrides(): Collection
    {
        return DB::table(self::TABLE)->orderByDesc('id')->get();
    }

    private function getLanguages(): array
    {
        return $this->localizationController->getLanguages();
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
        return $this->localizationController->getFiles();
    }

    private function searchInFile(string $file, string $query): array
    {
        $content = $this->loadFileContent($file);

        if (blank($content)) {
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
        $path = sprintf('%s/%s/%s',
            config()->string('snawbar-localization.path'),
            config()->string('snawbar-localization.base-locale'),
            $file
        );

        $content = @include $path;

        return is_array($content) ? $content : NULL;
    }

    private function matchesQuery(string $prefix, string $key, string $value, string $query): bool
    {
        return Str::contains([sprintf('%s.%s', $prefix, $key), $value], $query, ignoreCase: TRUE);
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

        if (blank($parts)) {
            return '';
        }

        return $this->extractTranslationValue($parts, $locale);
    }

    private function extractTranslationValue(array $parts, string $locale): string
    {
        $content = $this->loadLocaleFile($parts['file'], $locale);

        if (blank($content)) {
            return '';
        }

        $value = data_get($content, $parts['nested_key']);

        return is_string($value) ? $value : '';
    }

    private function parseTranslationKey(string $key): ?array
    {
        [$file, $nestedKey] = Str::of($key)->explode('.', 2);

        return match ((bool) $nestedKey) {
            TRUE => ['file' => $file, 'nested_key' => $nestedKey],
            FALSE => NULL,
        };
    }

    private function loadLocaleFile(string $file, string $locale): ?array
    {
        $path = sprintf('%s/%s/%s.php',
            config()->string('snawbar-localization.path'),
            $locale,
            $file
        );

        return match (file_exists($path)) {
            TRUE => $this->includeFile($path),
            FALSE => NULL,
        };
    }

    private function includeFile(string $path): ?array
    {
        $content = @include $path;

        return match (is_array($content)) {
            TRUE => $content,
            FALSE => NULL,
        };
    }

    private function saveOverrides(array $overrides): void
    {
        DB::table(self::TABLE)->upsert($overrides, ['key', 'locale'], ['value']);
    }

    private function clearCache(): void
    {
        TranslationCacheManager::clear();
    }

    private function updateOverride(int $id, string $value): void
    {
        DB::table(self::TABLE)->updateOrInsert(['id' => $id], ['value' => $value]);
    }

    private function deleteOverride(int $id): void
    {
        DB::table(self::TABLE)->where('id', $id)->delete();
    }
}

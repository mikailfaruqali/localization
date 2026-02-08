<?php

namespace Snawbar\Localization\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

class LocalizationController
{
    public function index()
    {
        $files = $this->getFiles();
        $missingKeys = $this->getMissingKeys($files);
        $fileStatuses = $this->getFileStatuses($files, $missingKeys);
        $sortedFiles = $this->sortFilesByProblems($files, $missingKeys);

        return view('snawbar-localization::file-selector', [
            'fileStatuses' => $fileStatuses,
            'missingKeys' => $missingKeys,
            'files' => $sortedFiles,
        ]);
    }

    public function compare(Request $request)
    {
        $request->validate([
            'file' => ['required', 'string', Rule::in($this->getFiles())],
        ]);

        $file = $request->file;
        $contents = $this->getFilesContent($this->getLanguages(), $file);
        $baseKeys = $this->getBaseKeys($contents);
        $missing = $this->getMissingKeys($file)[$file] ?? [];
        $missingcount = array_sum(array_map(count(...), $missing));

        return view('snawbar-localization::editor', [
            'baseKeys' => $baseKeys,
            'content' => $contents,
            'file' => $file,
            'totalKeys' => count($baseKeys),
            'missingCount' => $missingcount,
        ]);
    }

    public function update(Request $request)
    {
        foreach ($request->json('languages') as $language) {
            File::put(
                sprintf('%s/%s/%s', config()->string('snawbar-localization.path'), $language, $request->file),
                $this->generatePhpFileContent($request->get($language))
            );
        }

        return response()->json([
            'success' => 'Successfully updated',
        ]);
    }

    public function downloadLang(): BinaryFileResponse
    {
        $zipArchive = new ZipArchive;
        $zipFile = storage_path('lang-files.zip');
        $langPath = config()->string('snawbar-localization.path');

        $zipArchive->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (File::allFiles($langPath) as $file) {
            $zipArchive->addFile($file->getRealPath(), $file->getRelativePathname());
        }

        $zipArchive->close();

        return response()->download($zipFile)->deleteFileAfterSend(TRUE);
    }

    public function getLanguages(bool $withoutBase = FALSE): array
    {
        $basePath = config()->string('snawbar-localization.path');
        $baseLocale = config()->string('snawbar-localization.base-locale');

        return collect(File::directories($basePath))
            ->map(fn ($directory) => basename((string) $directory))
            ->when($withoutBase, fn ($collection) => $collection->reject(fn ($lang) => $lang === $baseLocale))
            ->sortByDesc(fn ($lang) => $lang === $baseLocale)
            ->values()
            ->all();
    }

    public function getFiles(): array
    {
        try {
            $basePath = sprintf('%s/%s',
                config()->string('snawbar-localization.path'),
                config()->string('snawbar-localization.base-locale')
            );

            $excludedFiles = config()->array('snawbar-localization.exclude');

            return collect(File::files($basePath))
                ->reject(fn ($file) => in_array($file->getFilename(), $excludedFiles) || $this->hasMulti($file->getRealPath()))
                ->map(fn ($file) => $file->getFilename())
                ->values()
                ->all();
        } catch (DirectoryNotFoundException $exception) {
            throw new Exception('Base Locale folder does not exist.', $exception->getCode(), $exception);
        } catch (Throwable $exception) {
            throw new Exception(
                sprintf('Snawbar Localization configuration error: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    private function getMissingKeys($targetFiles = NULL)
    {
        $missingKeys = [];
        $languages = $this->getLanguages(TRUE);
        $files = $targetFiles ? (array) $targetFiles : $this->getFiles();
        $baseLocale = config()->string('snawbar-localization.base-locale');

        foreach ($files as $file) {
            $fileContents = $this->getFilesContent([$baseLocale, ...$languages], $file);
            $baseContent = $fileContents->get($baseLocale, []);

            foreach ($languages as $language) {
                $languageContent = $fileContents->get($language, []);

                $missingByKey = array_diff_key($baseContent, $languageContent);

                $blankValues = array_filter($languageContent, blank(...));
                $blankByKey = array_intersect_key($baseContent, $blankValues);

                if ($missingByKey || $blankByKey) {
                    $missingKeys[$file][$language] = $missingByKey + $blankByKey;
                }
            }
        }

        return $missingKeys;
    }

    private function sortFilesByProblems(array $files, array $missingKeys)
    {
        return collect($files)
            ->sortBy(fn ($file) => ! isset($missingKeys[$file]))
            ->values()
            ->all();
    }

    private function getFileStatuses(array $files, array $missingKeys): array
    {
        $statuses = [];

        foreach ($files as $file) {
            $statuses[$file] = match (isset($missingKeys[$file])) {
                TRUE => sprintf('%d missing keys', array_sum(array_map(count(...), $missingKeys[$file]))),
                FALSE => 'All complete',
            };
        }

        return $statuses;
    }

    private function hasMulti(string $filePath): bool
    {
        return tap($data = include $filePath) && (! is_array($data) || collect($data)->contains(fn ($value) => is_array($value)));
    }

    private function getFilesContent(array $languages, string $file): Collection
    {
        $basePath = config()->string('snawbar-localization.path');

        return collect($languages)->mapWithKeys(
            fn ($language) => [$language => include sprintf('%s/%s/%s', $basePath, $language, $file)]
        );
    }

    private function getBaseKeys($selectedLanguageContents)
    {
        return array_keys($selectedLanguageContents->get(config()->string('snawbar-localization.base-locale')));
    }

    private function generatePhpFileContent(array $content): string
    {
        return sprintf("<?php\n\nreturn %s;\n", var_export($content, TRUE));
    }
}

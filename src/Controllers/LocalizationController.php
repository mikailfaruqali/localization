<?php

namespace Snawbar\Localization\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use ZipArchive;

class LocalizationController
{
    public function index()
    {
        $files = $this->getFiles();
        $missingKeys = $this->getMissingKeys($files);
        $sortedFiles = $this->sortFilesByProblems($files, $missingKeys);
        $fileStatuses = $this->getFileStatuses($files, $missingKeys);

        return view('snawbar-localization::file-selector', [
            'missingKeys' => $missingKeys,
            'files' => $sortedFiles,
            'fileStatuses' => $fileStatuses,
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
        $missingcount = array_sum(array_map('count', $missing));

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
        $request->merge([
            'languages' => json_decode($request->languages),
        ]);

        foreach ($request->languages as $language) {
            File::put(
                sprintf('%s/%s/%s', config('snawbar-localization.path'), $language, $request->file),
                $this->generatePhpFileContent($request->get($language))
            );
        }

        return response()->json([
            'success' => 'Successfully updated',
        ]);
    }

    public function downloadLang()
    {
        $zipArchive = new ZipArchive;
        $zipFile = storage_path('lang-files.zip');
        $langPath = config('snawbar-localization.path');

        if ($zipArchive->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            foreach (File::allFiles($langPath) as $file) {
                $zipArchive->addFile($file->getRealPath(), $file->getRelativePathname());
            }

            $zipArchive->close();
        }

        return response()->download($zipFile)->deleteFileAfterSend(TRUE);
    }

    public function getLanguages($withoutBase = FALSE)
    {
        return collect(File::directories(config('snawbar-localization.path')))
            ->when($withoutBase, fn ($collection) => $collection->reject(fn ($directory) => basename($directory) === config('snawbar-localization.base-locale')))
            ->sortByDesc(fn ($directory) => basename($directory) === config('snawbar-localization.base-locale'))
            ->map(fn ($directory) => basename($directory))
            ->toArray();
    }

    public function getFiles()
    {
        return collect(File::files(sprintf('%s/%s', config('snawbar-localization.path'), config('snawbar-localization.base-locale'))))
            ->reject(fn ($file) => in_array($file->getFilename(), config()->array('snawbar-localization.exclude')) || $this->hasMulti($file->getRealPath()))
            ->map(fn ($file) => $file->getFilename())
            ->toArray();
    }

    private function getMissingKeys($targetFiles = NULL)
    {
        $missingKeys = [];
        $languages = $this->getLanguages(TRUE);
        $files = $targetFiles ? (array) $targetFiles : $this->getFiles();
        $baseLocale = config('snawbar-localization.base-locale');

        foreach ($files as $file) {
            $fileContents = $this->getFilesContent([$baseLocale, ...$languages], $file);
            $baseContent = $fileContents->get($baseLocale, []);

            foreach ($languages as $language) {
                $languageContent = $fileContents->get($language, []);

                $missingByKey = array_diff_key($baseContent, $languageContent);

                $blankByKey = array_filter(
                    $languageContent,
                    fn ($languageValue) => blank($languageValue)
                );
                $blankByKey = array_intersect_key($baseContent, $blankByKey);

                if ($missingByKey || $blankByKey) {
                    $missingKeys[$file][$language] = $missingByKey + $blankByKey;
                }
            }
        }

        return $missingKeys;
    }

    private function sortFilesByProblems(array $files, array $missingKeys)
    {
        $filesWithProblems = [];
        $filesWithoutProblems = [];

        foreach ($files as $file) {
            if (isset($missingKeys[$file])) {
                $filesWithProblems[] = $file;
            } else {
                $filesWithoutProblems[] = $file;
            }
        }

        return array_merge($filesWithProblems, $filesWithoutProblems);
    }

    private function getFileStatuses(array $files, array $missingKeys)
    {
        $statuses = [];

        foreach ($files as $file) {
            if (isset($missingKeys[$file])) {
                $totalMissing = array_sum(array_map('count', $missingKeys[$file]));
                $statuses[$file] = sprintf('%d missing keys', $totalMissing);
            } else {
                $statuses[$file] = 'All complete';
            }
        }

        return $statuses;
    }

    private function hasMulti(string $filePath): bool
    {
        return tap($data = include $filePath) && (! is_array($data) || collect($data)->contains(fn ($value) => is_array($value)));
    }

    private function getFilesContent(array $languages, string $file)
    {
        return collect(array_merge(...array_map(fn ($lang) => [$lang => include sprintf('%s/%s/%s', config('snawbar-localization.path'), $lang, $file)], $languages)));
    }

    private function getBaseKeys($selectedLanguageContents)
    {
        return array_keys($selectedLanguageContents->get(config('snawbar-localization.base-locale')));
    }

    private function generatePhpFileContent(array $content): string
    {
        $lines = array_map(
            fn ($key, $value) => sprintf('    "%s" => "%s",', $key, $value),
            array_keys($content),
            $content
        );

        return "<?php\n\nreturn [\n" . implode("\n", $lines) . "\n];\n";
    }
}

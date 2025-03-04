<?php

namespace Snawbar\Localization\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class LocalizationController
{
    public function index()
    {
        return view('snawbar-localization::filter', [
            'missingKeys' => $this->getMissingKeys(),
            'languages' => $this->getLanguages(),
            'files' => $this->getFiles(),
        ]);
    }

    public function compare(Request $request, $file = NULL)
    {
        $request->mergeIfMissing([
            'file' => $file,
        ]);

        $request->validate([
            'file' => ['required', 'string', Rule::in($this->getFiles())],
        ]);

        $selectedLanguageContents = $this->getFilesContent($this->getLanguages(), $request->file);

        return view('snawbar-localization::compare', [
            'baseKeys' => $this->getBaseKeys($selectedLanguageContents),
            'content' => $selectedLanguageContents,
            'file' => $request->file,
        ]);
    }

    public function update(Request $request)
    {
        $request->merge([
            'languages' => json_decode($request->languages),
        ]);

        $this->validateRequestLanguages($request);

        foreach ($request->languages as $language) {
            File::put(
                path: sprintf('%s/%s/%s', config('snawbar-localization.path'), $language, $request->file),
                contents: $this->generatePhpFileContent($request->get($language))
            );
        }

        return response()->json([
            'success' => 'Successfully updated',
        ]);
    }

    private function getMissingKeys()
    {
        $missing = [];
        $languages = $this->getLanguages(withoutBase: TRUE);

        foreach ($this->getFiles() as $file) {
            $contents = $this->getFilesContent(array_merge([config('snawbar-localization.base-locale')], $languages), $file);

            foreach ($languages as $language) {
                $secContent = $contents->get($language);
                if ($diff = array_diff_key($contents->get(config('snawbar-localization.base-locale')), $secContent)) {
                    $missing[$file][$language] = $diff;
                }
            }
        }

        return collect($missing);
    }

    private function getLanguages($withoutBase = FALSE)
    {
        return collect(File::directories(config()->string('snawbar-localization.path')))
            ->when($withoutBase, fn ($collection) => $collection->reject(fn ($directory) => basename($directory) === config()->string('snawbar-localization.base-locale')))
            ->sortByDesc(fn ($directory) => basename($directory) === config()->string('snawbar-localization.base-locale'))
            ->map(fn ($directory) => basename($directory))
            ->toArray();
    }

    private function getFiles()
    {
        return collect(File::files(sprintf('%s/%s', config()->string('snawbar-localization.path'), config()->string('snawbar-localization.base-locale'))))
            ->reject(fn ($file) => in_array($file->getFilename(), config()->array('snawbar-localization.exclude')) || $this->hasMulti($file->getRealPath()))
            ->map(fn ($file) => $file->getFilename())
            ->toArray();
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
        return array_keys($selectedLanguageContents->get(config()->string('snawbar-localization.base-locale')));
    }

    private function validateRequestLanguages(Request $request)
    {
        return $request->validate(array_reduce($request->languages, fn ($rules, $lang) => $rules + [$lang . '.*' => 'required|string'], []));
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

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
            'languages' => $this->getLanguages(),
            'files' => $this->getFiles(),
        ]);
    }

    public function compare(Request $request)
    {
        $request->validate([
            'languages' => 'nullable|array',
            'languages.*' => ['nullable', 'string', Rule::in($this->getLanguages())],
            'file' => ['required', 'string', Rule::in($this->getFiles())],
        ]);

        $this->mergeLanguages($request);

        $selectedLanguageContents = $this->getFilesContent($request->languages, $request->file);

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
            'redirect' => route('snawbar.localization.view'),
        ]);
    }

    private function getLanguages()
    {
        return collect(File::directories(config()->string('snawbar-localization.path')))
            ->map(fn ($directory) => basename($directory))
            ->reject(fn ($directory) => $directory === config('app.locale'))
            ->toArray();
    }

    private function getFiles()
    {
        return collect(File::files(sprintf('%s/%s', config()->string('snawbar-localization.path'), config('app.locale'))))
            ->reject(fn ($file) => in_array($file->getFilename(), config()->array('snawbar-localization.exclude')))
            ->map(fn ($file) => $file->getFilename())
            ->toArray();
    }

    private function getFilesContent(array $languages, string $file)
    {
        return collect(array_merge(...array_map(fn ($lang) => [$lang => include sprintf('%s/%s/%s', config('snawbar-localization.path'), $lang, $file)], $languages)));
    }

    private function getBaseKeys($selectedLanguageContents)
    {
        return array_keys($selectedLanguageContents->get(config('app.locale')));
    }

    private function mergeLanguages(Request $request)
    {
        $request->merge([
            'languages' => array_merge([config('app.locale')], $request->get('languages', [])),
        ]);
    }

    private function validateRequestLanguages(Request $request)
    {
        return $request->validate(array_reduce($request->languages, fn ($rules, $lang) => $rules + [$lang . '.*' => 'required|string'], []));
    }

    private function generatePhpFileContent(array $content): string
    {
        $lines = array_map(
            fn ($key, $value) => sprintf("    '%s' => '%s',", $key, $value),
            array_keys($content),
            $content
        );

        return "<?php\n\nreturn  [\n" . implode("\n", $lines) . "\n];\n";
    }
}

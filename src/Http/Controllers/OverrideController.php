<?php

namespace Snawbar\Localization\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;

class OverrideController extends Controller
{
    public function index()
    {
        $overrides = $this->getOverrides();
        $languages = $this->getLanguages();

        return view('snawbar-localization::overrides', [
            'overrides' => $overrides,
            'languages' => $languages,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('query', '');
        $results = [];

        if (strlen($query) >= 2) {
            $fileResults = $this->searchInFiles($query);
            $overrideResults = $this->searchInOverrides($query);
            $results = array_merge($fileResults, $overrideResults);
        }

        return response()->json($results);
    }

    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'translations' => 'required|array',
        ]);

        try {
            $key = $request->key;
            $translations = $request->translations;

            foreach ($translations as $locale => $value) {
                if (filled($value)) {
                    DB::table('custom_translations')->updateOrInsert(
                        ['key' => $key, 'locale' => $locale],
                        [
                            'value' => $value,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }

            return response()->json(['success' => TRUE, 'message' => 'Translation override added successfully']);
        } catch (Exception) {
            return response()->json(['success' => FALSE, 'message' => 'Failed to add translation override'], 500);
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'translations' => 'required|array',
        ]);

        try {
            $key = $request->key;
            $translations = $request->translations;

            DB::table('custom_translations')->where('key', $key)->delete();

            foreach ($translations as $locale => $value) {
                if (filled($value)) {
                    DB::table('custom_translations')->insert([
                        'key' => $key,
                        'locale' => $locale,
                        'value' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return response()->json(['success' => TRUE, 'message' => 'Translation override updated successfully']);
        } catch (Exception) {
            return response()->json(['success' => FALSE, 'message' => 'Failed to update translation override'], 500);
        }
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        try {
            DB::table('custom_translations')->where('key', $request->key)->delete();
            return response()->json(['success' => TRUE, 'message' => 'Translation override deleted successfully']);
        } catch (Exception) {
            return response()->json(['success' => FALSE, 'message' => 'Failed to delete translation override'], 500);
        }
    }

    private function getOverrides()
    {
        $customTranslations = DB::table('custom_translations')->get();
        $grouped = [];

        foreach ($customTranslations as $customTranslation) {
            $grouped[$customTranslation->key][$customTranslation->locale] = $customTranslation->value;
        }

        return $grouped;
    }

    private function getLanguages()
    {
        $languagesPath = config('localization.lang_folder_path');

        if (!File::exists($languagesPath)) {
            return [];
        }

        return array_filter(
            File::directories($languagesPath),
            fn ($dir) => File::exists($dir . DIRECTORY_SEPARATOR . 'validation.php')
        );
    }

    private function searchInFiles(string $query)
    {
        $results = [];
        $languages = $this->getLanguages();
        $files = $this->getFiles();

        foreach ($files as $file) {
            $fileContent = $this->getFilesContent($languages, $file);

            foreach ($fileContent as $locale => $translations) {
                foreach ($translations as $key => $value) {
                    if (stripos($key, $query) !== FALSE || stripos($value, $query) !== FALSE) {
                        if (!isset($results[$key])) {
                            $results[$key] = [];
                        }

                        $results[$key][$locale] = $value;
                    }
                }
            }
        }

        return $results;
    }

    private function searchInOverrides(string $query)
    {
        $customTranslations = DB::table('custom_translations')
            ->whereLike('key', sprintf('%%%s%%', $query))
            ->orWhereLike('value', sprintf('%%%s%%', $query))
            ->get();

        $results = [];
        foreach ($customTranslations as $customTranslation) {
            $results[$customTranslation->key][$customTranslation->locale] = $customTranslation->value;
        }

        return $results;
    }

    private function getFiles()
    {
        $filesPath = config('localization.lang_folder_path');
        $languages = $this->getLanguages();

        if (blank($languages)) {
            return [];
        }

        $firstLanguageDir = $languages[0];
        $files = [];

        if (File::exists($firstLanguageDir)) {
            foreach (File::files($firstLanguageDir) as $file) {
                if ($file->getExtension() === 'php') {
                    $files[] = $file->getFilenameWithoutExtension();
                }
            }
        }

        return $files;
    }

    private function getFilesContent(array $languages, string $file)
    {
        $content = [];
        $languagesPath = config('localization.lang_folder_path');

        foreach ($languages as $language) {
            $languageCode = basename($language);
            $filePath = $languagesPath . DIRECTORY_SEPARATOR . $languageCode . DIRECTORY_SEPARATOR . $file . '.php';

            if (File::exists($filePath)) {
                $content[$languageCode] = include $filePath;
            }
        }

        return $content;
    }
}

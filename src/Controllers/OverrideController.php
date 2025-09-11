<?php

namespace Snawbar\Localization\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class OverrideController extends Controller
{
    public function index()
    {
        $overrides = DB::table('override_translations')
            ->orderByDesc('id')
            ->get();

        $languages = app(LocalizationController::class)->getLanguages();

        return view('snawbar-localization::overrides', [
            'overrides' => $overrides,
            'languages' => $languages,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = collect(app(LocalizationController::class)->getFiles())
            ->flatMap(fn ($file) => $this->searchInFile($file, $query))
            ->values()
            ->toArray();

        return response()->json($results);
    }

    public function store(Request $request)
    {
        $request->validate([
            'language' => 'required',
            'key' => 'required|string',
            'value' => 'required|string',
        ]);

        DB::table('override_translations')->updateOrInsert(['key' => $request->key, 'locale' => $request->language], [
            'value' => $request->value,
        ]);

        return response()->json([
            'success' => 'Successfully added',
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'value' => 'required|string',
        ]);

        DB::table('override_translations')->updateOrInsert(['id' => $request->id], [
            'value' => $request->value,
        ]);

        return response()->json([
            'success' => 'Successfully updated',
        ]);
    }

    public function destroy(Request $request)
    {
        DB::table('override_translations')->where('id', $request->id)->delete();

        return response()->json([
            'success' => 'Successfully deleted',
        ]);
    }

    private function searchInFile(string $file, string $query): array
    {
        $content = @include sprintf('%s/%s/%s',
            config('snawbar-localization.path'),
            config('snawbar-localization.base-locale'),
            $file
        );

        if (! is_array($content)) {
            return [];
        }

        $prefix = pathinfo($file, PATHINFO_FILENAME);

        return collect($content)
            ->filter(fn ($value, $key) => stripos(sprintf('%s.%s', $prefix, $key), $query) !== FALSE || stripos($value, $query) !== FALSE)
            ->map(fn ($value, $key) => [
                'id' => sprintf('%s.%s', $prefix, $key),
                'text' => sprintf('%s.%s', $prefix, $key),
                'value' => $value,
            ])
            ->toArray();
    }
}

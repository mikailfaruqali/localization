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
            'language' => 'required',
            'key' => 'required|string',
            'value' => 'required|string',
        ]);

        DB::table('override_translations')->updateOrInsert(['key' => $request->key, 'locale' => $request->language], [
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
}

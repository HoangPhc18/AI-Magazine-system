<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AISettings;
use Illuminate\Http\Request;

class AISettingsController extends Controller
{
    public function index()
    {
        $settings = AISettings::first();
        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
            'model' => 'required|string',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:1|max:4000',
            'top_p' => 'required|numeric|min:0|max:1',
            'frequency_penalty' => 'required|numeric|min:-2|max:2',
            'presence_penalty' => 'required|numeric|min:-2|max:2'
        ]);

        $settings = AISettings::first();
        if (!$settings) {
            $settings = new AISettings();
        }

        $settings->fill($validated);
        $settings->save();

        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }
} 
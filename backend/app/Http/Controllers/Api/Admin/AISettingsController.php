<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AISettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AISettingsController extends Controller
{
    public function index()
    {
        $settings = AISettings::first();
        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'openai_api_key' => 'nullable|string',
            'ollama_api_url' => 'required|url',
            'model_name' => 'required|string',
            'max_tokens' => 'required|integer|min:1|max:4000',
            'temperature' => 'required|numeric|min:0|max:2',
            'top_p' => 'required|numeric|min:0|max:1',
            'frequency_penalty' => 'required|numeric|min:-2|max:2',
            'presence_penalty' => 'required|numeric|min:-2|max:2',
            'system_prompt' => 'nullable|string',
            'is_active' => 'required|boolean'
        ]);

        $settings = AISettings::first();
        
        if (!$settings) {
            $settings = AISettings::create($validated);
        } else {
            $settings->update($validated);
        }

        // Clear cache after updating settings
        Cache::forget('ai_settings');

        return response()->json([
            'message' => 'AI settings updated successfully',
            'settings' => $settings
        ]);
    }

    public function testConnection()
    {
        $settings = AISettings::first();
        
        if (!$settings) {
            return response()->json([
                'success' => false,
                'message' => 'AI settings not configured'
            ], 400);
        }

        try {
            // Test OpenAI connection if API key is provided
            if ($settings->openai_api_key) {
                $client = \OpenAI::client($settings->openai_api_key);
                $response = $client->models()->list();
                if (!$response) {
                    throw new \Exception('Failed to connect to OpenAI');
                }
            }

            // Test Ollama connection
            $client = new \GuzzleHttp\Client();
            $response = $client->get($settings->ollama_api_url . '/api/tags');
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to connect to Ollama');
            }

            return response()->json([
                'success' => true,
                'message' => 'Successfully connected to AI services'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to AI services: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resetSettings()
    {
        $settings = AISettings::first();
        
        if ($settings) {
            $settings->delete();
        }

        // Clear cache after resetting settings
        Cache::forget('ai_settings');

        return response()->json([
            'message' => 'AI settings reset to default values'
        ]);
    }
} 
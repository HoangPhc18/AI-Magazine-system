<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AISetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AISettingsController extends Controller
{
    public function index()
    {
        $aiSettings = AISetting::first() ?? new AISetting([
            'provider' => 'openai',
            'api_key' => '',
            'api_url' => '',
            'model_name' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'rewrite_prompt_template' => 'Rewrite the following article to make it more engaging and informative: {article}',
            'auto_approval' => false,
            'max_daily_rewrites' => 5
        ]);

        return view('admin.ai-settings.index', compact('aiSettings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|in:openai,anthropic,mistral,ollama,custom',
            'api_key' => 'nullable|string',
            'api_url' => 'nullable|string|url_or_local_host',
            'model_name' => 'required|string',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:1|max:16000',
            'rewrite_prompt_template' => 'required|string',
            'auto_approval' => 'boolean',
            'max_daily_rewrites' => 'required|integer|min:0|max:1000',
        ]);

        // Convert checkbox value
        $validated['auto_approval'] = $request->has('auto_approval');

        // Find or create settings
        $aiSettings = AISetting::firstOrNew();
        $aiSettings->fill($validated);
        $aiSettings->save();

        // Clear any cached settings
        Cache::forget('ai_settings');

        return redirect()->route('admin.ai-settings.index')
            ->with('success', 'AI settings updated successfully.');
    }

    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|in:openai,anthropic,mistral,ollama,custom',
            'api_key' => 'nullable|string',
            'api_url' => 'nullable|string|url_or_local_host',
            'model_name' => 'required|string',
        ]);

        $apiUrl = $this->getApiUrl($validated['provider'], $validated['api_url']);
        $testResult = $this->testApiConnection($validated['provider'], $apiUrl, $validated['api_key'], $validated['model_name']);

        if ($testResult['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Connection successful! API is responding correctly.',
                'models' => $testResult['models'] ?? []
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $testResult['error']
            ], 400);
        }
    }

    public function reset()
    {
        $defaultSettings = [
            'provider' => 'openai',
            'api_key' => '',
            'api_url' => '',
            'model_name' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'rewrite_prompt_template' => 'Rewrite the following article to make it more engaging and informative: {article}',
            'auto_approval' => false,
            'max_daily_rewrites' => 5
        ];

        $aiSettings = AISetting::firstOrNew();
        $aiSettings->fill($defaultSettings);
        $aiSettings->save();

        // Clear any cached settings
        Cache::forget('ai_settings');

        return redirect()->route('admin.ai-settings.index')
            ->with('success', 'AI settings have been reset to default values.');
    }

    private function getApiUrl($provider, $customUrl = null)
    {
        switch ($provider) {
            case 'openai':
                return 'https://api.openai.com/v1';
            case 'anthropic':
                return 'https://api.anthropic.com/v1';
            case 'mistral':
                return 'https://api.mistral.ai/v1';
            case 'ollama':
                return $customUrl ?: 'http://localhost:11434';
            case 'custom':
                return $customUrl;
            default:
                return '';
        }
    }

    private function testApiConnection($provider, $apiUrl, $apiKey, $modelName)
    {
        try {
            switch ($provider) {
                case 'openai':
                    return $this->testOpenAI($apiUrl, $apiKey);
                case 'anthropic':
                    return $this->testAnthropic($apiUrl, $apiKey);
                case 'mistral':
                    return $this->testMistral($apiUrl, $apiKey);
                case 'ollama':
                    return $this->testOllama($apiUrl, $modelName);
                case 'custom':
                    return $this->testCustom($apiUrl, $apiKey, $modelName);
                default:
                    return ['success' => false, 'error' => 'Unsupported provider'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function testOpenAI($apiUrl, $apiKey)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json'
        ])->get($apiUrl . '/models');

        if ($response->successful()) {
            $data = $response->json();
            $models = collect($data['data'])->pluck('id')->toArray();
            return ['success' => true, 'models' => $models];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    private function testAnthropic($apiUrl, $apiKey)
    {
        // For Anthropic, we'll just do a basic authentication check
        // since they don't have a simple 'models' endpoint
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json'
        ])->get($apiUrl . '/models');

        return ['success' => $response->successful(), 'error' => $response->successful() ? '' : $response->body()];
    }

    private function testMistral($apiUrl, $apiKey)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json'
        ])->get($apiUrl . '/models');

        if ($response->successful()) {
            $data = $response->json();
            $models = collect($data['data'])->pluck('id')->toArray();
            return ['success' => true, 'models' => $models];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    private function testOllama($apiUrl, $modelName)
    {
        $response = Http::post($apiUrl . '/api/tags');

        if ($response->successful()) {
            $data = $response->json();
            $models = collect($data['models'])->pluck('name')->toArray();
            return ['success' => true, 'models' => $models];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    private function testCustom($apiUrl, $apiKey, $modelName)
    {
        // For custom providers, we'll just do a basic ping test
        $response = Http::get($apiUrl);
        return ['success' => $response->successful(), 'error' => $response->successful() ? '' : $response->body()];
    }
}

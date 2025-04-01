<?php

namespace App\Services;

use App\Models\AISetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected $settings;

    public function __construct()
    {
        $this->settings = AISetting::first() ?? new AISetting([
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
    }

    /**
     * Generate AI content based on a prompt
     *
     * @param string $prompt The prompt to generate content from
     * @return array The result of the AI generation
     */
    public function generateContent($prompt)
    {
        try {
            switch ($this->settings->provider) {
                case 'openai':
                    return $this->callOpenAI($prompt);
                case 'anthropic':
                    return $this->callAnthropic($prompt);
                case 'mistral':
                    return $this->callMistral($prompt);
                case 'ollama':
                    return $this->callOllama($prompt);
                case 'custom':
                    return $this->callCustom($prompt);
                default:
                    return [
                        'success' => false,
                        'content' => '',
                        'error' => 'Unsupported AI provider: ' . $this->settings->provider
                    ];
            }
        } catch (\Exception $e) {
            Log::error('AI content generation error: ' . $e->getMessage(), [
                'provider' => $this->settings->provider,
                'prompt' => $prompt
            ]);
            
            return [
                'success' => false,
                'content' => '',
                'error' => 'Error generating content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Rewrite an article using AI
     *
     * @param string $articleContent The original article content
     * @return array The result of the rewriting
     */
    public function rewriteArticle($articleContent)
    {
        $prompt = str_replace('{article}', $articleContent, $this->settings->rewrite_prompt_template);
        return $this->generateContent($prompt);
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAI($prompt)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->settings->api_key,
            'Content-Type' => 'application/json'
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->settings->model_name,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that specializes in rewriting content in an engaging, informative, and SEO-friendly manner.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $this->settings->temperature,
            'max_tokens' => $this->settings->max_tokens
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'content' => $data['choices'][0]['message']['content'] ?? '',
                'error' => ''
            ];
        }

        return [
            'success' => false,
            'content' => '',
            'error' => $response->body()
        ];
    }

    /**
     * Call Anthropic API
     */
    protected function callAnthropic($prompt)
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->settings->api_key,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json'
        ])->post('https://api.anthropic.com/v1/complete', [
            'model' => $this->settings->model_name,
            'prompt' => "\n\nHuman: " . $prompt . "\n\nAssistant:",
            'max_tokens_to_sample' => $this->settings->max_tokens,
            'temperature' => $this->settings->temperature
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'content' => $data['completion'] ?? '',
                'error' => ''
            ];
        }

        return [
            'success' => false,
            'content' => '',
            'error' => $response->body()
        ];
    }

    /**
     * Call Mistral AI API
     */
    protected function callMistral($prompt)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->settings->api_key,
            'Content-Type' => 'application/json'
        ])->post('https://api.mistral.ai/v1/chat/completions', [
            'model' => $this->settings->model_name,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that specializes in rewriting content in an engaging, informative, and SEO-friendly manner.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $this->settings->temperature,
            'max_tokens' => $this->settings->max_tokens
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'content' => $data['choices'][0]['message']['content'] ?? '',
                'error' => ''
            ];
        }

        return [
            'success' => false,
            'content' => '',
            'error' => $response->body()
        ];
    }

    /**
     * Call Ollama API
     */
    protected function callOllama($prompt)
    {
        $apiUrl = $this->settings->api_url ?: 'http://localhost:11434';
        
        $response = Http::post($apiUrl . '/api/generate', [
            'model' => $this->settings->model_name,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => $this->settings->temperature,
                'num_predict' => $this->settings->max_tokens
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'content' => $data['response'] ?? '',
                'error' => ''
            ];
        }

        return [
            'success' => false,
            'content' => '',
            'error' => $response->body()
        ];
    }

    /**
     * Call Custom API
     */
    protected function callCustom($prompt)
    {
        if (empty($this->settings->api_url)) {
            return [
                'success' => false,
                'content' => '',
                'error' => 'Custom API URL is not configured'
            ];
        }

        $headers = [];
        if (!empty($this->settings->api_key)) {
            $headers['Authorization'] = 'Bearer ' . $this->settings->api_key;
        }
        
        $response = Http::withHeaders($headers)
            ->post($this->settings->api_url, [
                'model' => $this->settings->model_name,
                'prompt' => $prompt,
                'temperature' => $this->settings->temperature,
                'max_tokens' => $this->settings->max_tokens
            ]);

        if ($response->successful()) {
            $data = $response->json();
            // We can't predict the exact structure of a custom API response
            // So we'll use a simple check for common fields
            $content = $data['content'] ?? 
                      $data['text'] ?? 
                      $data['response'] ?? 
                      $data['output'] ?? 
                      $data['completion'] ?? 
                      $data['message'] ?? 
                      '';
            
            return [
                'success' => true,
                'content' => $content,
                'error' => ''
            ];
        }

        return [
            'success' => false,
            'content' => '',
            'error' => $response->body()
        ];
    }
} 
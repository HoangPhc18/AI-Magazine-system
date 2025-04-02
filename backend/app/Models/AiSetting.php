<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AISetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'api_key',
        'api_url',
        'model_name',
        'temperature',
        'max_tokens',
        'rewrite_prompt_template',
        'auto_approval',
        'max_daily_rewrites'
    ];

    protected $casts = [
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'auto_approval' => 'boolean',
        'max_daily_rewrites' => 'integer',
    ];

    /**
     * Get a specific setting or all settings as an array
     * 
     * @param string|null $key The specific setting to retrieve
     * @param mixed $default Default value if the setting doesn't exist
     * @return mixed The setting value or all settings as an array
     */
    public static function getSetting($key = null, $default = null)
    {
        $settings = static::first();
        
        if (!$settings) {
            return $default;
        }
        
        if ($key === null) {
            return $settings->toArray();
        }
        
        return $settings->$key ?? $default;
    }

    /**
     * Get the API URL based on the provider
     * 
     * @return string The complete API URL
     */
    public function getApiEndpointAttribute()
    {
        switch ($this->provider) {
            case 'openai':
                return 'https://api.openai.com/v1/chat/completions';
            case 'anthropic':
                return 'https://api.anthropic.com/v1/complete';
            case 'mistral':
                return 'https://api.mistral.ai/v1/chat/completions';
            case 'ollama':
                return ($this->api_url ?: 'http://localhost:11434') . '/api/generate';
            case 'custom':
                return $this->api_url;
            default:
                return '';
        }
    }
} 
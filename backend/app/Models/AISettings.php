<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AISettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'openai_api_key',
        'ollama_api_url',
        'model_name',
        'max_tokens',
        'temperature',
        'top_p',
        'frequency_penalty',
        'presence_penalty',
        'system_prompt',
        'is_active'
    ];

    protected $casts = [
        'max_tokens' => 'integer',
        'temperature' => 'float',
        'top_p' => 'float',
        'frequency_penalty' => 'float',
        'presence_penalty' => 'float',
        'is_active' => 'boolean'
    ];
} 
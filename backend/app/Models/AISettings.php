<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AISettings extends Model
{
    protected $table = 'ai_settings';

    protected $fillable = [
        'api_key',
        'model',
        'temperature',
        'max_tokens',
        'top_p',
        'frequency_penalty',
        'presence_penalty'
    ];

    protected $casts = [
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'top_p' => 'float',
        'frequency_penalty' => 'float',
        'presence_penalty' => 'float'
    ];
} 
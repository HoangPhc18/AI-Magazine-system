<?php

namespace Database\Seeders;

use App\Models\AISettings;
use Illuminate\Database\Seeder;

class AISettingsSeeder extends Seeder
{
    public function run(): void
    {
        AISettings::create([
            'openai_api_key' => 'your-api-key-here',
            'ollama_api_url' => 'http://localhost:11434',
            'model_name' => 'gemma:2b',
            'max_tokens' => 2000,
            'temperature' => 0.7,
            'top_p' => 0.9,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0,
            'system_prompt' => 'Hãy viết lại bài viết sau một cách sáng tạo và hấp dẫn hơn, giữ nguyên thông tin chính nhưng thay đổi cách diễn đạt: {content}',
            'is_active' => true
        ]);
    }
} 
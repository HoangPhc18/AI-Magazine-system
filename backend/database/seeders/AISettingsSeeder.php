<?php

namespace Database\Seeders;

use App\Models\AISettings;
use Illuminate\Database\Seeder;

class AISettingsSeeder extends Seeder
{
    public function run()
    {
        AISettings::create([
            'setting_key' => 'openai',
            'setting_value' => json_encode([
                'api_key' => '',
                'model' => 'gpt-3.5-turbo',
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'top_p' => 1,
                'frequency_penalty' => 0,
                'presence_penalty' => 0
            ]),
            'api_key' => '',
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ]);
    }
} 
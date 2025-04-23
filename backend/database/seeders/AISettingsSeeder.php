<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AISettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kiểm tra nếu bảng không tồn tại
        if (!Schema::hasTable('a_i_settings')) {
            $this->command->error('Bảng a_i_settings không tồn tại! Hãy chạy migrations trước.');
            return;
        }
        
        // Kiểm tra nếu đã có cài đặt
        if (DB::table('a_i_settings')->count() > 0) {
            $this->command->info('Cài đặt AI đã tồn tại. Bỏ qua seed.');
            return;
        }
        
        // Tạo cài đặt AI mặc định
        DB::table('a_i_settings')->insert([
            'provider' => 'openai',
            'api_key' => env('OPENAI_API_KEY', 'sk-your-api-key'),
            'api_url' => 'https://api.openai.com/v1',
            'model_name' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'rewrite_prompt_template' => 'Hãy viết lại bài viết sau với ngôn ngữ dễ hiểu, hấp dẫn và giữ nguyên ý nghĩa chính: {{content}}',
            'auto_approval' => false,
            'max_daily_rewrites' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->command->info('Đã tạo cài đặt AI mặc định.');
    }
} 
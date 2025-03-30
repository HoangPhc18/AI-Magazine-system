<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Thêm người dùng
        DB::table('users')->insert([
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Editor',
                'email' => 'editor@example.com',
                'password' => Hash::make('password'),
                'role' => 'editor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'User',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Thêm danh mục
        DB::table('categories')->insert([
            [
                'name' => 'Công Nghệ',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kinh Tế',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Thêm bài viết
        DB::table('articles')->insert([
            [
                'title' => 'AI đang thay đổi thế giới',
                'url' => 'https://news.com/ai',
                'source' => 'News Site',
                'content' => 'AI đang tạo ra cách mạng công nghệ',
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Thị trường chứng khoán tăng trưởng mạnh',
                'url' => 'https://news.com/stock',
                'source' => 'Finance Blog',
                'content' => 'Thị trường chứng khoán đang tăng mạnh',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Thêm bài viết AI viết lại
        DB::table('rewritten_articles')->insert([
            [
                'article_id' => 1,
                'rewritten_content' => 'Trí tuệ nhân tạo đang dẫn đầu cuộc cách mạng số.',
                'status' => 'approved',
                'reviewed_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Thêm lịch sử chỉnh sửa
        DB::table('edit_history')->insert([
            [
                'rewritten_article_id' => 1,
                'edited_by' => 1,
                'previous_content' => 'AI đang cách mạng hóa ngành công nghiệp.',
                'edited_at' => now(),
            ],
        ]);

        // Thêm cài đặt AI
        DB::table('ai_settings')->insert([
            [
                'setting_key' => 'rewrite_model',
                'setting_value' => 'Gemma 2',
                'updated_at' => now(),
            ],
            [
                'setting_key' => 'max_length',
                'setting_value' => '100',
                'updated_at' => now(),
            ],
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Chỉ chạy các seeder cơ bản
            AdminSeeder::class,     // Tạo admin
            CategorySeeder::class,  // Tạo danh mục
            AISettingsSeeder::class, // Cài đặt AI
            ArticleSeeder::class, // Tạo bài viết
            UserSeeder::class,
            RewrittenArticleSeeder::class, // Tạo bài viết đã được viết lại
            
        ]);
    }
} 
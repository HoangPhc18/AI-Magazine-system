<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            CategorySeeder::class,
            // Bỏ comment dưới đây khi tạo xong bảng articles
            // ArticleSeeder::class,
            // RewrittenArticleSeeder::class,
        ]);
    }
} 
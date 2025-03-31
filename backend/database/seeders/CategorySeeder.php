<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Công nghệ',
                'slug' => 'cong-nghe',
                'description' => 'Các bài viết về công nghệ và đổi mới'
            ],
            [
                'name' => 'Khoa học',
                'slug' => 'khoa-hoc',
                'description' => 'Các bài viết về khám phá và nghiên cứu khoa học'
            ],
            [
                'name' => 'Sức khỏe',
                'slug' => 'suc-khoe',
                'description' => 'Các bài viết về sức khỏe và đời sống'
            ],
            [
                'name' => 'Kinh doanh',
                'slug' => 'kinh-doanh',
                'description' => 'Các bài viết về kinh doanh và khởi nghiệp'
            ]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
} 
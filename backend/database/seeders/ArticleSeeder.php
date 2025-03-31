<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();
        
        foreach ($categories as $category) {
            Article::create([
                'title' => "Bài viết mẫu về {$category->name}",
                'content' => "Đây là nội dung mẫu cho bài viết về {$category->name}. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.",
                'category_id' => $category->id,
                'status' => 'draft'
            ]);
        }
    }
} 
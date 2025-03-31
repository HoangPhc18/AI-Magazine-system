<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\RewrittenArticle;
use Illuminate\Database\Seeder;

class RewrittenArticleSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();
        $articles = Article::all();
        
        foreach ($categories as $category) {
            $article = $articles->where('category_id', $category->id)->first();
            
            if ($article) {
                RewrittenArticle::create([
                    'title' => "Bài viết đã viết lại về {$category->name}",
                    'content' => "Đây là nội dung đã được viết lại cho bài viết về {$category->name}. Nội dung đã được cải thiện và tối ưu hóa để tăng tính hấp dẫn và dễ đọc hơn. Các thông tin đã được cập nhật và bổ sung thêm các chi tiết mới.",
                    'category_id' => $category->id,
                    'original_article_id' => $article->id,
                    'status' => 'pending'
                ]);
            }
        }
    }
} 
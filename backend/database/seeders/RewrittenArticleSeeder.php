<?php

namespace Database\Seeders;

use App\Models\RewrittenArticle;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RewrittenArticleSeeder extends Seeder
{
    public function run()
    {
        // Get categories and users
        $categories = Category::all();
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        // Create 5 rewritten articles
        for ($i = 1; $i <= 5; $i++) {
            $title = "Bài viết được viết lại bởi AI $i";
            $slug = Str::slug($title);
            
            RewrittenArticle::create([
                'title' => $title,
                'slug' => $slug,
                'content' => "Đây là nội dung mẫu cho bài viết được viết lại bởi AI số $i. Nội dung này được tạo tự động bởi seeder. Trong ứng dụng thực tế, đây sẽ là nội dung được viết lại bởi mô hình AI.\n\nLorem ipsum dolor sit amet, consectetur adipiscing elit. Bài viết này nhằm mục đích minh họa cách hoạt động của hệ thống. Nội dung sẽ được cập nhật khi hệ thống được triển khai chính thức và người dùng tạo ra nội dung thực tế.",
                'meta_title' => "Tiêu đề meta cho $title",
                'meta_description' => "Mô tả meta cho $title. Điều này sẽ giúp tối ưu hóa SEO.",
                'user_id' => $admin->id,
                'category_id' => $categories->isNotEmpty() ? $categories->random()->id : Category::factory()->create()->id,
                'status' => 'pending',
                'published_at' => null,
            ]);
        }
    }
} 
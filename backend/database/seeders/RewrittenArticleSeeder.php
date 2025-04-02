<?php

namespace Database\Seeders;

use App\Models\RewrittenArticle;
use App\Models\ApprovedArticle;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RewrittenArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy admin user để gán bài viết
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $this->command->info('Không tìm thấy admin. Vui lòng chạy AdminSeeder trước.');
            return;
        }
        
        // Lấy tất cả danh mục
        $categories = Category::all();
        if ($categories->isEmpty()) {
            $this->command->info('Không tìm thấy danh mục nào. Vui lòng chạy CategorySeeder trước.');
            return;
        }
        
        // Lấy các bài viết đã duyệt để tạo bài viết rewritten từ chúng
        $approvedArticles = ApprovedArticle::where('status', 'published')->take(3)->get();
        if ($approvedArticles->isEmpty()) {
            $this->command->info('Không tìm thấy bài viết đã duyệt. Vui lòng chạy ArticleSeeder trước.');
            return;
        }
        
        foreach ($approvedArticles as $approvedArticle) {
            // Tạo bài viết đang chờ duyệt
            $pendingArticle = $this->createRewrittenArticle(
                $approvedArticle,
                $admin->id,
                $approvedArticle->category_id,
                'pending'
            );
            
            $this->command->info("Đã tạo bài viết rewritten (đang chờ duyệt): {$pendingArticle->title}");
            
            // Tạo bài viết đã bị từ chối
            $rejectedArticle = $this->createRewrittenArticle(
                $approvedArticle,
                $admin->id,
                $approvedArticle->category_id,
                'rejected'
            );
            
            $this->command->info("Đã tạo bài viết rewritten (đã bị từ chối): {$rejectedArticle->title}");
        }
    }
    
    /**
     * Tạo một bài viết rewritten từ bài viết đã duyệt
     */
    private function createRewrittenArticle(ApprovedArticle $approvedArticle, int $userId, int $categoryId, string $status): RewrittenArticle
    {
        $title = $approvedArticle->title . ' (Đang viết lại)';
        $content = $this->modifyContent($approvedArticle->content);
        
        return RewrittenArticle::create([
            'title' => $title,
            'slug' => Str::slug($title) . '-' . uniqid(),
            'content' => $content,
            'category_id' => $categoryId,
            'user_id' => $userId,
            'original_article_id' => $approvedArticle->id,
            'status' => $status,
            'meta_title' => $title,
            'meta_description' => Str::limit(strip_tags($content), 160),
            'featured_image' => $approvedArticle->featured_image,
            'ai_generated' => true,
            'created_at' => now()->subDays(rand(1, 7)),
            'updated_at' => now(),
        ]);
    }
    
    /**
     * Sửa đổi nội dung bài viết để tạo phiên bản rewritten
     */
    private function modifyContent(string $content): string
    {
        // Thêm vài đoạn văn giả định là từ AI
        $aiParagraph = '<p>Bài viết này đang được viết lại với sự hỗ trợ của AI. Nội dung sẽ được cải thiện và mở rộng để cung cấp thông tin chi tiết và hữu ích hơn cho người đọc. Điều này bao gồm việc thêm các ví dụ, thống kê, và phân tích sâu hơn về chủ đề.</p>';
        
        // Thêm đoạn văn AI vào giữa nội dung
        $contentParts = explode('</p>', $content, 2);
        if (count($contentParts) > 1) {
            return $contentParts[0] . '</p>' . $aiParagraph . $contentParts[1];
        }
        
        return $content . $aiParagraph;
    }
} 
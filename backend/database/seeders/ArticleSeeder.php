<?php

namespace Database\Seeders;

use App\Models\ApprovedArticle;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kiểm tra cấu trúc bảng approved_articles
        if (!Schema::hasTable('approved_articles')) {
            $this->command->error('Bảng approved_articles không tồn tại!');
            return;
        }
        
        // Lấy danh sách các cột trong bảng
        $columns = Schema::getColumnListing('approved_articles');
        $this->command->info('Các cột trong bảng approved_articles: ' . implode(', ', $columns));
        
        // Lấy admin user để gán bài viết
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $this->command->error('Không tìm thấy admin! Hãy chạy AdminSeeder trước.');
            return;
        }
        
        // Lấy tất cả danh mục để phân phối bài viết
        $categories = Category::all();
        
        if ($categories->isEmpty()) {
            $this->command->error('Không tìm thấy danh mục nào. Vui lòng chạy CategorySeeder trước.');
            return;
        }
        
        $articles = [
            [
                'title' => 'Xu hướng công nghệ mới nhất năm 2024',
                'content' => '<p>Năm 2024 đánh dấu sự bùng nổ của nhiều công nghệ đột phá. Trí tuệ nhân tạo tiếp tục phát triển nhanh chóng với các ứng dụng mới trong y tế, tài chính và giáo dục. Công nghệ blockchain đã vượt ra khỏi tiền điện tử để được áp dụng trong chuỗi cung ứng và xác minh danh tính.</p><p>Metaverse cũng đang trở thành xu hướng lớn với các không gian ảo ngày càng thực tế và hấp dẫn. Ngoài ra, xe điện và năng lượng tái tạo đang ghi nhận sự tăng trưởng đáng kể khi thế giới hướng tới tương lai xanh hơn.</p>',
                'category' => 'Công nghệ'
            ],
            [
                'title' => 'Phát hiện mới về vũ trụ đang thay đổi hiểu biết của chúng ta',
                'content' => '<p>Các nhà thiên văn học vừa công bố phát hiện mới về một hệ sao xa xôi có tiềm năng chứa sự sống. Kính viễn vọng Webb đã chụp được hình ảnh chi tiết về khí quyển của một hành tinh ngoài hệ mặt trời, cho thấy dấu hiệu của nước và khí metan - hai thành phần quan trọng cho sự sống như chúng ta biết.</p><p>Phát hiện này đánh dấu một bước tiến quan trọng trong việc tìm kiếm sự sống ngoài Trái Đất và mở ra nhiều câu hỏi mới về nguồn gốc của sự sống trong vũ trụ.</p>',
                'category' => 'Khoa học'
            ],
            [
                'title' => 'Chiến lược kinh doanh bền vững trong thời đại số',
                'content' => '<p>Các doanh nghiệp đang phải đối mặt với những thách thức và cơ hội mới trong kỷ nguyên số. Chuyển đổi số không còn là lựa chọn mà là điều kiện sống còn. Từ việc áp dụng trí tuệ nhân tạo trong phân tích dữ liệu đến việc xây dựng trải nghiệm khách hàng liền mạch qua nhiều kênh, các công ty đang tìm cách tăng cường hiệu quả vận hành và mở rộng thị trường.</p><p>Bên cạnh đó, tính bền vững đang trở thành yếu tố then chốt trong chiến lược kinh doanh. Người tiêu dùng ngày càng ưu tiên các thương hiệu có trách nhiệm với môi trường và xã hội, buộc các doanh nghiệp phải điều chỉnh hoạt động để đáp ứng kỳ vọng này.</p>',
                'category' => 'Kinh doanh'
            ],
            [
                'title' => 'Phương pháp dinh dưỡng mới giúp tăng cường sức khỏe tim mạch',
                'content' => '<p>Nghiên cứu mới từ các chuyên gia dinh dưỡng đã chỉ ra rằng chế độ ăn giàu thực vật kết hợp với một số loại cá có thể giảm đáng kể nguy cơ mắc bệnh tim. Nghiên cứu kéo dài 10 năm với hơn 10.000 người tham gia cho thấy những người tuân thủ chế độ ăn này có tỷ lệ mắc bệnh tim thấp hơn 27% so với nhóm đối chứng.</p><p>Các nhà nghiên cứu nhấn mạnh tầm quan trọng của việc kết hợp nhiều loại rau, trái cây, các loại hạt và cá béo như cá hồi, cá ngừ trong chế độ ăn hàng ngày. Đặc biệt, chất chống oxy hóa từ thực vật và axit béo omega-3 từ cá đóng vai trò quan trọng trong việc bảo vệ sức khỏe tim mạch.</p>',
                'category' => 'Sức khỏe'
            ],
            [
                'title' => 'Khám phá văn hóa ẩm thực Việt Nam qua góc nhìn hiện đại',
                'content' => '<p>Ẩm thực Việt Nam đang ngày càng được thế giới công nhận không chỉ vì hương vị đặc trưng mà còn vì triết lý cân bằng âm dương và sử dụng nguyên liệu tươi sống. Từ phở Hà Nội đến bánh mì Sài Gòn, mỗi món ăn đều mang trong mình câu chuyện về lịch sử và văn hóa của một vùng miền.</p><p>Ngày nay, các đầu bếp trẻ Việt Nam đang tìm cách tái diễn giải ẩm thực truyền thống với kỹ thuật hiện đại, tạo ra những trải nghiệm ẩm thực mới mẻ nhưng vẫn giữ được bản sắc Việt. Xu hướng này không chỉ thu hút du khách quốc tế mà còn giúp giới trẻ Việt Nam kết nối lại với di sản văn hóa của mình.</p>',
                'category' => 'Đời sống'
            ],
            [
                'title' => 'Những điểm đến ẩn của châu Á đang thu hút du khách khám phá',
                'content' => '<p>Bên cạnh những điểm đến nổi tiếng như Bangkok hay Bali, châu Á còn sở hữu nhiều viên ngọc ẩn đang dần được các du khách khám phá. Từ làng chài yên bình ở Phú Quốc, Việt Nam đến những ngôi đền cổ ít người biết đến ở Myanmar, những điểm đến này mang đến trải nghiệm văn hóa đích thực mà không bị thương mại hóa quá mức.</p><p>Xu hướng du lịch bền vững cũng đang thúc đẩy việc khám phá các điểm đến mới này, khi du khách tìm kiếm những trải nghiệm có ý nghĩa và có trách nhiệm với môi trường và cộng đồng địa phương. Các tour du lịch sinh thái và homestay với người dân địa phương đang trở nên phổ biến, mang lại lợi ích kinh tế cho cộng đồng trong khi vẫn bảo tồn được văn hóa và môi trường tự nhiên.</p>',
                'category' => 'Du lịch'
            ],
        ];
        
        foreach ($articles as $articleData) {
            // Tìm category tương ứng
            $category = $categories->where('name', $articleData['category'])->first();
            
            if (!$category) {
                $this->command->warn("Không tìm thấy danh mục '{$articleData['category']}'. Bỏ qua bài viết.");
                continue;
            }
            
            $title = $articleData['title'];
            $slug = Str::slug($title);
            
            // Tạo bài viết mới với các trường cơ bản
            $articleAttributes = [
                'title' => $title,
                'slug' => $slug,
                'content' => $articleData['content'],
                'category_id' => $category->id,
                'user_id' => $admin->id,
                'status' => 'published',
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now(),
            ];
            
            // Thêm các trường tùy chọn nếu chúng tồn tại trong bảng
            if (in_array('meta_title', $columns)) {
                $articleAttributes['meta_title'] = $title;
            }
            
            if (in_array('meta_description', $columns)) {
                $articleAttributes['meta_description'] = Str::limit(strip_tags($articleData['content']), 160);
            }
            
            if (in_array('published_at', $columns)) {
                $articleAttributes['published_at'] = now()->subDays(rand(1, 30));
            }
            
            if (in_array('word_count', $columns)) {
                $articleAttributes['word_count'] = str_word_count(strip_tags($articleData['content']));
            }
            
            if (in_array('is_ai_generated', $columns)) {
                $articleAttributes['is_ai_generated'] = false;
            }
            
            // Tạo bài viết và ghi log
            try {
                $article = ApprovedArticle::create($articleAttributes);
                $this->command->info("Đã tạo bài viết: {$article->title}");
            } catch (\Exception $e) {
                $this->command->error("Lỗi khi tạo bài viết '{$title}': " . $e->getMessage());
            }
        }
    }
} 
<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'Công nghệ' => 'Các bài viết về xu hướng và đổi mới công nghệ mới nhất',
            'Khoa học' => 'Khám phá khoa học và kết quả nghiên cứu mới',
            'Kinh doanh' => 'Tin tức kinh doanh, chiến lược và phân tích thị trường',
            'Sức khỏe' => 'Mẹo sức khỏe, đột phá y tế và lời khuyên về sức khỏe tổng thể',
            'Đời sống' => 'Chủ đề về cuộc sống hàng ngày, văn hóa, giải trí và sở thích',
            'Du lịch' => 'Điểm đến du lịch, hướng dẫn và trải nghiệm du lịch'
        ];
        
        foreach ($categories as $name => $description) {
            Category::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $description
            ]);
        }
    }
} 
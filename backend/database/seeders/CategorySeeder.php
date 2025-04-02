<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kiểm tra nếu bảng không tồn tại
        if (!Schema::hasTable('categories')) {
            $this->command->error('Bảng categories không tồn tại! Hãy chạy migrations trước.');
            return;
        }

        $categories = [
            ['name' => 'Công nghệ', 'slug' => 'cong-nghe', 'description' => 'Tin tức về công nghệ, AI, blockchain, gadget mới nhất'],
            ['name' => 'Kinh doanh', 'slug' => 'kinh-doanh', 'description' => 'Thông tin về kinh doanh, thị trường và tài chính'],
            ['name' => 'Giải trí', 'slug' => 'giai-tri', 'description' => 'Tin tức về phim ảnh, âm nhạc, nghệ sĩ và sự kiện giải trí'],
            ['name' => 'Thể thao', 'slug' => 'the-thao', 'description' => 'Cập nhật tin tức thể thao trong nước và quốc tế'],
            ['name' => 'Đời sống', 'slug' => 'doi-song', 'description' => 'Thông tin về đời sống, gia đình, sức khỏe và xu hướng'],
        ];

        $count = 0;
        foreach ($categories as $category) {
            // Kiểm tra xem category đã tồn tại chưa
            $existing = DB::table('categories')->where('slug', $category['slug'])->first();
            
            if (!$existing) {
                DB::table('categories')->insert([
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'description' => $category['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

        if ($count > 0) {
            $this->command->info("Đã tạo $count danh mục mới.");
        } else {
            $this->command->info('Không có danh mục mới nào được tạo. Tất cả đã tồn tại.');
        }
    }
} 
# Backend Magazine AI

Backend cho hệ thống tạo nội dung tự động bằng AI, sử dụng Laravel làm nền tảng.

## Giới thiệu

Backend Magazine AI là một ứng dụng Laravel quản lý nội dung được thu thập và tạo bởi các dịch vụ AI. Hệ thống này cung cấp:

- API quản lý nội dung
- Giao diện quản trị
- Tương tác với các dịch vụ AI
- Quản lý quyền và người dùng
- Hiển thị nội dung cho người đọc

## Yêu cầu hệ thống

- PHP 8.1+
- MySQL 8.0+
- Composer
- Node.js và NPM (cho việc biên dịch assets)

## Cài đặt và thiết lập

### Cài đặt thủ công

1. Clone repository và truy cập thư mục backend
```bash
git clone https://github.com/yourusername/magazine-ai-system.git
cd magazine-ai-system/backend
```

2. Cài đặt các phụ thuộc PHP
```bash
composer install
```

3. Cài đặt các phụ thuộc Node.js
```bash
npm install
```

4. Sao chép file môi trường và cấu hình
```bash
cp .env.example .env
php artisan key:generate
```

5. Cấu hình cơ sở dữ liệu trong file `.env`
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aimagazinedb
DB_USERNAME=root
DB_PASSWORD=
```

6. Thực hiện migration và tạo dữ liệu mẫu
```bash
php artisan migrate
php artisan db:seed
```

7. Biên dịch assets
```bash
npm run dev
```

8. Khởi động server
```bash
php artisan serve
```

### Cài đặt với Docker

1. Đảm bảo Docker và Docker Compose đã được cài đặt

2. Từ thư mục gốc của dự án, khởi động dịch vụ
```bash
docker compose up -d
```

## Cấu trúc ứng dụng

### Thư mục chính

- `app/` - Chứa các controllers, models, middleware và service classes
- `config/` - Chứa các file cấu hình
- `database/` - Chứa migrations và seeders
- `public/` - Điểm truy cập và asset files
- `resources/` - Chứa views, assets chưa biên dịch và file ngôn ngữ
- `routes/` - Chứa các định nghĩa route
- `storage/` - Chứa file tạm thời và uploads
- `tests/` - Chứa test files

### Các thành phần chính

1. **Models** (`app/Models/`):
   - `Article.php` - Quản lý bài viết
   - `Category.php` - Quản lý danh mục
   - `FacebookPost.php` - Quản lý bài viết từ Facebook
   - `Media.php` - Quản lý media (hình ảnh, video)
   - `User.php` - Quản lý người dùng

2. **Controllers** (`app/Http/Controllers/`):
   - `ArticleController.php` - Xử lý bài viết
   - `CategoryController.php` - Xử lý danh mục
   - `FacebookPostController.php` - Xử lý bài viết từ Facebook
   - `MediaController.php` - Xử lý media
   - `UserController.php` - Xử lý người dùng
   - `AdminController.php` - Xử lý tác vụ quản trị

3. **Services** (`app/Services/`):
   - `AIService.php` - Tương tác với các dịch vụ AI
   - `WebsiteConfigService.php` - Quản lý cấu hình website

## API Documentation

Tài liệu API (Swagger/OpenAPI) có sẵn và có thể được truy cập theo các cách sau:

### Xem tài liệu API trực tuyến

Truy cập http://localhost:8000/docs để xem tài liệu API được tạo bằng Swagger UI.

### Tệp định nghĩa API

Các tệp định nghĩa API có sẵn trong thư mục `docs/`:
- `swagger.yaml`: Định nghĩa API ở định dạng YAML
- `swagger.json`: Định nghĩa API ở định dạng JSON

### Sử dụng API

1. **Xác thực**: 
   - Đăng ký người dùng mới: `POST /api/auth/register`
   - Đăng nhập: `POST /api/auth/login`
   - Sử dụng token nhận được trong header `Authorization: Bearer {token}`

2. **Endpoints chính**:
   - Bài viết: `/api/articles`
   - Danh mục: `/api/categories`
   - Facebook Posts: `/api/facebook-posts`
   - Media: `/api/media`
   - Quản lý người dùng (Admin): `/api/admin/users`
   - Cài đặt AI: `/api/ai-settings` hoặc `/api/admin/ai-settings`

## Kiểm tra tích hợp AI

Hệ thống bao gồm các script kiểm tra kết nối với dịch vụ AI:

```bash
# Kiểm tra kết nối với tất cả dịch vụ AI
php check-all-ai-services.php

# Kiểm tra kết nối trực tiếp với AI service
php test-ai-connection.php

# Kiểm tra kết nối qua Laravel
php test-laravel-ai-connection.php
```

## Cấu hình AI

Cấu hình kết nối đến các dịch vụ AI được lưu trong file `.env`:

```
AI_SERVICE_URL=http://localhost:55025
AI_SCRAPER_ENDPOINT=/scraper
AI_REWRITE_ENDPOINT=/rewrite
AI_KEYWORD_REWRITE_ENDPOINT=/keyword-rewrite
AI_FACEBOOK_SCRAPER_ENDPOINT=/facebook-scraper
AI_FACEBOOK_REWRITE_ENDPOINT=/facebook-rewrite
```

## Chạy tests

```bash
php artisan test
```

## Nhật ký và gỡ lỗi

Xem log Laravel
```bash
tail -f storage/logs/laravel.log
```

## Bảo mật

Đảm bảo thay đổi các thông tin nhạy cảm trong file `.env` và cài đặt quyền truy cập thích hợp:

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

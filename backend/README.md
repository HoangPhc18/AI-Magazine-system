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

- XAMPP (PHP 8.1+, MySQL 8.0+, Apache)
- Composer
- Node.js và NPM (cho việc biên dịch assets)

## Cài đặt và thiết lập

### Cài đặt với XAMPP

1. Clone repository và truy cập thư mục backend
```bash
git clone https://github.com/HoanqPhuc/magazine-ai-system
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
DB_DATABASE=tap_chi_dien_tu
DB_USERNAME=tap_chi_dien_tu
DB_PASSWORD=Nh[Xg3KT06)FI91X
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

### Cấu hình Apache Virtual Host

1. Mở file `c:\xampp\apache\conf\extra\httpd-vhosts.conf` và thêm:

```
<VirtualHost *:80>
    DocumentRoot "F:/magazine-ai-system/backend/public"
    ServerName magazine.test
    
    <Directory "F:/magazine-ai-system/backend/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/magazine-error.log"
    CustomLog "logs/magazine-access.log" combined
</VirtualHost>
```

2. Mở file `c:\Windows\System32\drivers\etc\hosts` với quyền administrator và thêm:
```
127.0.0.1 magazine.test
```

3. Khởi động lại Apache từ XAMPP Control Panel

4. Truy cập ứng dụng tại http://magazine.test

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

Xem log Laravel:
```bash
tail -f storage/logs/laravel.log
```

Xem log Apache:
```bash
tail -f c:\xampp\apache\logs\magazine-error.log
tail -f c:\xampp\apache\logs\magazine-access.log
```



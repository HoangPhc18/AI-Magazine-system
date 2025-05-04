# Hệ thống Magazine AI

Hệ thống tự động thu thập, viết lại và tạo nội dung cho trang web tạp chí điện tử, sử dụng AI để tạo nội dung chất lượng cao.

## Mô tả hệ thống

Hệ thống Magazine AI là một giải pháp toàn diện để tự động hóa việc tạo nội dung cho trang web tạp chí điện tử, bao gồm các tính năng chính:

- **Thu thập nội dung tự động**: Thu thập tin tức từ nhiều nguồn khác nhau
- **Viết lại nội dung**: Sử dụng AI để viết lại nội dung thu thập một cách độc đáo
- **Tạo nội dung từ từ khóa**: Tạo bài viết mới dựa trên từ khóa đầu vào
- **Thu thập dữ liệu từ Facebook**: Tự động thu thập nội dung từ Facebook
- **Quản lý nội dung**: Backend quản lý và hiển thị nội dung đã xử lý

## Cấu trúc hệ thống

Hệ thống gồm 2 thành phần chính:

1. **Backend** (Laravel): Quản lý và hiển thị nội dung
2. **AI Service**: Cung cấp các dịch vụ AI xử lý nội dung
   - Scraper Service
   - Rewrite Service
   - Keyword Rewrite Service
   - Facebook Scraper Service
   - Facebook Rewrite Service

## Yêu cầu hệ thống

### Backend
- XAMPP (PHP 8.1+, MySQL 8.0+, Apache)
- Composer
- Node.js và NPM

### AI Service
- Docker Desktop
- Ít nhất 4GB RAM cho container AI
- Ít nhất 10GB dung lượng ổ đĩa trống

## Cài đặt Backend

1. Clone repository
```bash
git clone https://github.com/HoanqPhuc/magazine-ai-system
cd magazine-ai-system
```

2. Cài đặt phụ thuộc
```bash
cd backend
composer install
npm install
```

3. Cấu hình môi trường
```bash
cp .env.example .env
php artisan key:generate
```

4. Cấu hình cơ sở dữ liệu trong file `.env`
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tap_chi_dien_tu
DB_USERNAME=tap_chi_dien_tu
DB_PASSWORD=Nh[Xg3KT06)FI91X
```

5. Migrate và seed dữ liệu
```bash
php artisan migrate
php artisan db:seed
```

6. Cấu hình Apache Virtual Host
- Thêm cấu hình vào file `c:\xampp\apache\conf\extra\httpd-vhosts.conf`:
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

- Thêm domain vào file `c:\Windows\System32\drivers\etc\hosts`:
```
127.0.0.1 magazine.test
```

7. Khởi động lại Apache từ XAMPP Control Panel

## Cài đặt AI Service

1. Di chuyển vào thư mục AI service
```bash
cd ai_service
```

2. Chạy script khởi động (Windows)
```bash
run-container.bat
```

## Truy cập hệ thống

- **Frontend/Backend**: http://magazine.test
- **Backend API**: http://magazine.test/api
- **API Documentation**: http://magazine.test/docs
- **AI Service API**: http://localhost:55025

## Kiểm tra hệ thống

Kiểm tra kết nối với các dịch vụ AI:

```bash
cd backend
php check-all-ai-services.php
```

## Gỡ lỗi

### Backend
```bash
# Xem log Laravel
tail -f backend/storage/logs/laravel.log

# Xem log Apache
tail -f c:\xampp\apache\logs\magazine-error.log
```

### AI Service
```bash
# Xem log AI Service
docker logs ai-service-all
```






# Hệ Thống Tạp Chí AI

Giải pháp toàn diện để tự động tạo nội dung cho tạp chí điện tử sử dụng AI nhằm tạo ra nội dung chất lượng cao với sự can thiệp tối thiểu của con người.

## Tổng Quan

Hệ thống Tạp Chí AI tự động hóa toàn bộ quy trình tạo nội dung cho tạp chí điện tử, với các tính năng:

- **Thu Thập Nội Dung Tự Động**: Thu thập tin tức từ nhiều nguồn đa dạng
- **Viết Lại Nội Dung**: Sử dụng AI để viết lại nội dung thu thập một cách độc đáo
- **Tạo Nội Dung Từ Từ Khóa**: Tạo các bài viết mới dựa trên từ khóa đầu vào
- **Thu Thập Dữ Liệu Facebook**: Tự động thu thập nội dung từ Facebook
- **Quản Lý Nội Dung**: Backend để quản lý và hiển thị nội dung đã xử lý

## Kiến Trúc Hệ Thống

Hệ thống bao gồm hai thành phần chính:

1. **Backend** (Laravel): Quản lý và hiển thị nội dung
2. **Dịch Vụ AI**: Các dịch vụ xử lý AI chuyên biệt
   - Dịch Vụ Thu Thập (Scraper): Thu thập nội dung từ các nguồn khác nhau
   - Dịch Vụ Viết Lại (Rewrite): Chuyển đổi nội dung đã thu thập
   - Dịch Vụ Viết Từ Từ Khóa (Keyword Rewrite): Tạo nội dung từ các từ khóa
   - Dịch Vụ Thu Thập Facebook (Facebook Scraper): Trích xuất dữ liệu từ Facebook
   - Dịch Vụ Viết Lại Facebook (Facebook Rewrite): Xử lý nội dung Facebook

## Cấu Trúc Thư Mục và Mô Tả Chức Năng

```
magazine-ai-system/
├── ai_service/                # Dịch vụ AI
│   ├── scraper/               # Thu thập dữ liệu từ các trang web
│   ├── rewrite/               # Dịch vụ viết lại nội dung
│   ├── keyword_rewrite/       # Tạo nội dung từ từ khóa
│   ├── facebook_scraper/      # Thu thập dữ liệu từ Facebook
│   ├── facebook_rewrite/      # Xử lý nội dung Facebook
│   ├── nginx/                 # Cấu hình máy chủ web Nginx
│   ├── Dockerfile             # Cấu hình Docker
│   ├── run-container.bat      # Script chạy container AI trên Windows
│   └── start.sh               # Script khởi động dịch vụ AI
│
├── backend/                   # Mã nguồn Laravel Backend
│   ├── app/                   # Mã nguồn ứng dụng chính
│   │   ├── Console/           # Các lệnh artisan
│   │   ├── Exceptions/        # Xử lý ngoại lệ
│   │   ├── Http/              # Controllers, Middlewares, Requests
│   │   ├── Models/            # Các model dữ liệu
│   │   └── Services/          # Các dịch vụ kết nối AI
│   │
│   ├── config/                # Cấu hình ứng dụng
│   ├── database/              # Migrations và seeds
│   │   ├── migrations/        # Cấu trúc bảng dữ liệu
│   │   └── seeders/          # Dữ liệu mẫu
│   │
│   ├── public/                # Thư mục công khai
│   ├── resources/             # Views, assets, JS, CSS
│   │   ├── js/                # Mã nguồn JavaScript
│   │   ├── css/               # Style sheets
│   │   └── views/             # Blade templates
│   │
│   ├── routes/                # Định nghĩa routes
│   │   ├── api.php            # API routes
│   │   └── web.php            # Web routes
│   │
│   ├── storage/               # Lưu trữ tạm thời, logs
│   ├── tests/                 # Unit tests
│   ├── check-all-ai-services.php  # Kiểm tra kết nối đến dịch vụ AI
│   └── artisan                # CLI Laravel
│
├── vendor/                    # Dependencies PHP
└── README.md                  # Tài liệu hướng dẫn
```

### Mô Tả Các Thành Phần Chính

#### Backend (Laravel)

- **Controllers**: Xử lý các yêu cầu HTTP, điều hướng đến services và trả về responses
- **Models**: Đại diện cho cấu trúc dữ liệu và tương tác với cơ sở dữ liệu
- **Services**: Xử lý logic nghiệp vụ và kết nối với các dịch vụ AI
- **Migrations**: Định nghĩa cấu trúc cơ sở dữ liệu
- **Routes**: Định nghĩa các endpoints API và web routes

#### Dịch Vụ AI

- **Scraper Service**: Thu thập dữ liệu từ các trang web tin tức, blog và các nguồn trực tuyến khác
  - Hỗ trợ nhiều trang web khác nhau thông qua các adapters
  - Xử lý HTML và trích xuất nội dung có ý nghĩa
  
- **Rewrite Service**: Viết lại nội dung đã thu thập
  - Sử dụng mô hình NLP để tạo nội dung độc đáo
  - Giữ nguyên ý nghĩa gốc nhưng với cách diễn đạt mới
  
- **Keyword Rewrite Service**: Tạo nội dung hoàn toàn mới từ từ khóa
  - Phân tích từ khóa và tạo nội dung có cấu trúc
  - Tối ưu hóa SEO cho nội dung được tạo
  
- **Facebook Scraper Service**: Thu thập dữ liệu từ Facebook
  - Thu thập bài viết, bình luận từ các trang và nhóm
  - Xử lý dữ liệu đa phương tiện như hình ảnh và video
  
- **Facebook Rewrite Service**: Xử lý và tối ưu hóa nội dung từ Facebook
  - Viết lại nội dung từ Facebook với định dạng đúng
  - Tích hợp với hệ thống phân loại nội dung

## Yêu Cầu Hệ Thống

### Backend
- XAMPP (PHP 8.1+, MySQL 8.0+, Apache)
- Composer
- Node.js 

### Dịch Vụ AI
- Docker Desktop
- Tối thiểu 4GB RAM cho các container AI
- Ít nhất 10GB dung lượng ổ đĩa trống

## Hướng Dẫn Cài Đặt

### Cài Đặt Backend

1. Clone repository
```bash
git clone https://github.com/HoanqPhuc/magazine-ai-system
cd magazine-ai-system
```

2. Cài đặt các dependency
```bash
cd backend
composer install
```

3. Cấu hình môi trường
```bash
cp .env.example .env
php artisan key:generate
```

4. Tạo và thiết lập cơ sở dữ liệu

    a. Đăng nhập vào MySQL và tạo người dùng với quyền hạn đầy đủ:
    ```bash
    mysql -u root -p
    ```
    
    b. Tạo người dùng mới cho cơ sở dữ liệu:
    ```sql
    CREATE USER 'tap_chi_dien_tu'@'127.0.0.1' IDENTIFIED BY 'Nh[Xg3KT06)FI91X';
    ```
    
    c. Tạo cơ sở dữ liệu mới:
    ```sql
    CREATE DATABASE tap_chi_dien_tu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ```
    
    d. Cấp quyền cho người dùng:
    ```sql
    GRANT ALL PRIVILEGES ON tap_chi_dien_tu.* TO 'tap_chi_dien_tu'@'127.0.0.1';
    FLUSH PRIVILEGES;
    EXIT;
    ```
    
    e. Thiết lập thông tin cơ sở dữ liệu trong file `.env`:
    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=tap_chi_dien_tu
    DB_USERNAME=tap_chi_dien_tu
    DB_PASSWORD=Nh[Xg3KT06)FI91X
    ```

5. Migrate và seed cơ sở dữ liệu
```bash
php artisan migrate --seed
```

6. Cấu hình Apache Virtual Host
   - Thêm vào file `httpd-vhosts.conf` (đường dẫn tùy thuộc vào cài đặt XAMPP của bạn):
   ```
   <VirtualHost *:80>
       DocumentRoot "/đường/dẫn/đến/magazine-ai-system/backend/public"
       ServerName magazine.test
       
       <Directory "/đường/dẫn/đến/magazine-ai-system/backend/public">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog "logs/magazine-error.log"
       CustomLog "logs/magazine-access.log" combined
   </VirtualHost>
   ```

   - Thêm vào file hosts của bạn:
   ```
   127.0.0.1 magazine.test
   ```

7. Khởi động lại Apache từ XAMPP Control Panel

### Cài Đặt Dịch Vụ AI

1. Di chuyển đến thư mục dịch vụ AI
```bash
cd ai_service
```

2. Chạy script khởi động
   - Cho Windows:
   ```bash
   run-container.bat
   ```
   - Cho Linux/Mac:
   ```bash
   bash run-container.sh
   ```

## Truy Cập Hệ Thống

- **Giao Diện Web**: http://magazine.test
- **Backend API**: http://magazine.test/api
- **Tài Liệu API**: http://magazine.test/docs
- **API Dịch Vụ AI**: http://localhost:55025

## Kiểm Tra Hệ Thống

Để xác minh kết nối đến tất cả các dịch vụ AI:

```bash
cd backend
php check-all-ai-services.php
```

## Xử Lý Sự Cố

### Vấn Đề Backend
```bash
# Xem nhật ký Laravel
tail -f backend/storage/logs/laravel.log

# Xem nhật ký Apache
tail -f /đường/dẫn/đến/xampp/apache/logs/magazine-error.log
```

### Vấn Đề Dịch Vụ AI
```bash
# Xem nhật ký Dịch Vụ AI
docker logs ai-service-all
```

## Tài Nguyên Bổ Sung

- [Tài Liệu Laravel](https://laravel.com/docs)
- [Tài Liệu Docker](https://docs.docker.com/)









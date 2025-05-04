# Hệ thống Magazine AI

Hệ thống tự động thu thập, viết lại và tạo nội dung cho trang web tin tức, sử dụng AI để tạo nội dung chất lượng cao.

## Tổng quan hệ thống

Hệ thống Magazine AI là một giải pháp toàn diện để tự động hóa việc tạo nội dung cho trang web tin tức, bao gồm:

- Thu thập dữ liệu tự động từ nhiều nguồn
- Viết lại nội dung bằng AI
- Tạo nội dung mới từ từ khóa
- Tích hợp nội dung với trang web
- Thu thập và xử lý dữ liệu từ Facebook

## Cấu trúc dự án

Dự án bao gồm các module chính:

1. **Backend**: Backend Laravel cho trang web, quản lý và hiển thị nội dung
2. **AI Service**:
   - **Scraper**: Module tự động thu thập tin tức từ nhiều nguồn
   - **Rewrite**: Module tự động viết lại nội dung bài viết 
   - **Keyword Rewrite**: Module tự động tạo bài viết từ từ khóa
   - **Facebook Scraper**: Thu thập dữ liệu từ Facebook
   - **Facebook Rewrite**: Viết lại nội dung thu thập từ Facebook

## Yêu cầu hệ thống

- Docker và Docker Compose
- Ít nhất 8GB RAM
- Ít nhất 20GB dung lượng ổ đĩa trống
- Ollama (cho mô hình AI cục bộ)

## Cài đặt và chạy

### Các bước cài đặt

1. Clone repository
```bash
git clone https://github.com/HoanqPhuc/magazine-ai-system
cd magazine-ai-system
```

2. Tạo các file môi trường
```bash
# Backend .env
cp backend/.env.example backend/.env

# AI Service
cd ai_service
./start.sh
```

3. Khởi động dịch vụ với Docker Compose
```bash
docker compose up -d
```



### Kiểm tra trạng thái

```bash
# Xem log của tất cả các dịch vụ
docker compose logs -f

# Xem log của một dịch vụ cụ thể
docker compose logs -f ai-service-all
```

## Dịch vụ và cổng

- **Backend**: http://localhost:8000
- **Backend API**: http://localhost:8000/api
- **AI Service API**: http://localhost:55025
  - Scraper: /scraper/
  - Rewrite: /rewrite/
  - Keyword Rewrite: /keyword-rewrite/
  - Facebook Scraper: /facebook-scraper/
  - Facebook Rewrite: /facebook-rewrite/
- **Ollama**: http://localhost:11434

## Lịch trình tự động

Hệ thống được cấu hình để chạy tự động theo lịch trình:

- **Scraper**: Chạy vào lúc 0h00, 6h00, 12h00, 18h00 mỗi ngày
- **Rewrite**: Chạy vào lúc 0h30, 6h30, 12h30, 18h30 mỗi ngày (30 phút sau khi Scraper chạy)
- **Facebook Scraper**: Chạy vào lúc 1h00, 7h00, 13h00, 19h00 mỗi ngày
- **Facebook Rewrite**: Chạy vào lúc 1h30, 7h30, 13h30, 19h30 mỗi ngày
- **Keyword Rewrite**: Luôn hoạt động (API service), xử lý yêu cầu từ Backend



## Kiểm tra trạng thái dịch vụ AI

```bash
curl http://localhost:55025/health
```

## Chạy thủ công

Mặc dù hệ thống đã được cấu hình để chạy tự động, bạn vẫn có thể kích hoạt thủ công:

```bash
# Chạy Scraper thủ công
curl -X POST http://localhost:55025/scraper/run

# Chạy Rewrite thủ công
curl -X POST http://localhost:55025/rewrite/run

# Chạy Facebook Scraper thủ công
curl -X POST http://localhost:55025/facebook-scraper/run

# Chạy Facebook Rewrite thủ công
curl -X POST http://localhost:55025/facebook-rewrite/run
```



### Khắc phục sự cố

1. **Kiểm tra log**:
```bash
docker compose logs -f
```

2. **Khởi động lại một dịch vụ**:
```bash
docker compose restart service_name
```

3. **Khởi động lại toàn bộ hệ thống**:
```bash
docker compose down
docker compose up -d
```

### Lỗi thường gặp

1. **Lỗi kết nối đến backend:**
   - Kiểm tra biến môi trường `BACKEND_URL` trong file `.env` của AI Service
   - Đảm bảo backend đang chạy và có thể truy cập

2. **Lỗi kết nối đến cơ sở dữ liệu:**
   - Kiểm tra cấu hình `DB_HOST` trong các file `.env`
   - Đảm bảo cơ sở dữ liệu đang chạy và có thể truy cập






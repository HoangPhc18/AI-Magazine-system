# Hệ thống Magazine AI

Hệ thống tự động thu thập, viết lại và tạo nội dung cho trang web tin tức, sử dụng AI để tạo nội dung chất lượng.

## Cấu trúc dự án

Dự án bao gồm các module chính:

1. **Backend**: Backend Laravel cho trang web
2. **Scraper**: Module tự động thu thập tin tức từ nhiều nguồn
3. **Rewrite**: Module tự động viết lại nội dung bài viết
4. **Keyword Rewrite**: Module tự động tạo bài viết từ từ khóa

## Lịch trình tự động

Hệ thống được cấu hình để chạy tự động theo lịch trình:

- **Scraper**: Chạy vào lúc 0h00, 6h00, 12h00, 18h00 mỗi ngày
- **Rewrite**: Chạy vào lúc 0h30, 6h30, 12h30, 18h30 mỗi ngày (30 phút sau khi Scraper chạy)
- **Keyword Rewrite**: Luôn hoạt động (API service), xử lý yêu cầu từ Backend

## Cài đặt và chạy

### Yêu cầu

- Docker và Docker Compose

### Các bước cài đặt

1. Clone repository
```bash
git clone https://github.com/yourusername/magazine-ai-system.git
cd magazine-ai-system
```

2. Tạo các file môi trường

```bash
# Backend .env
cp backend/.env.example backend/.env

# Scraper .env
echo "DB_HOST=db
DB_PORT=3306
DB_NAME=aimagazinedb
DB_USER=root
DB_PASSWORD=root_password" > ai_service/scraper/.env

# Rewrite .env
echo "DB_HOST=db
DB_PORT=3306
DB_NAME=aimagazinedb
DB_USER=root
DB_PASSWORD=root_password
OLLAMA_BASE_URL=http://ollama:11434
OLLAMA_MODEL=gemma2" > ai_service/rewrite/.env

# Keyword Rewrite .env
echo "PORT=5000
HOST=0.0.0.0
DEBUG=False
OLLAMA_MODEL=gemma2:latest
OLLAMA_HOST=http://ollama:11434" > ai_service/keyword_rewrite/.env
```

3. Khởi động dịch vụ với Docker Compose

```bash
docker compose up -d
```

4. Cài đặt model Ollama

```bash
docker exec -it magazine-ollama ollama pull gemma2:latest
```

### Kiểm tra trạng thái

```bash
# Xem log của tất cả các dịch vụ
docker compose logs -f

# Xem log của một dịch vụ cụ thể
docker compose logs -f scraper
docker compose logs -f rewrite
docker compose logs -f keyword_rewrite
```

## API Endpoints

- **Backend API**: http://localhost:8000/api
- **Keyword Rewrite API**: http://localhost:5000/api/keyword_rewrite/process
- **Scraper API**: http://localhost:8080/run (POST)
- **Rewrite API**: http://localhost:8080/run (POST)

## Chạy thủ công

Mặc dù hệ thống đã được cấu hình để chạy tự động theo lịch trình, bạn vẫn có thể kích hoạt các quy trình theo cách thủ công:

```bash
# Chạy Scraper thủ công
curl -X POST http://localhost:8080/run

# Chạy Rewrite thủ công
curl -X POST http://localhost:8080/run
```

## Cấu hình lịch trình

Lịch trình được cấu hình trong file `Dockerfile.scheduler`. Để thay đổi lịch trình:

1. Chỉnh sửa file `Dockerfile.scheduler`
2. Xây dựng lại và khởi động lại container:

```bash
docker compose build scheduler
docker compose up -d scheduler
```

## Bảo trì

### Sao lưu dữ liệu

```bash
docker compose exec db mysqldump -uroot -proot_password aimagazinedb > backup.sql
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
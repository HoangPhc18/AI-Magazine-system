# Hướng dẫn đóng gói và chạy dự án AI Service với cổng 55025

## Giới thiệu

Tài liệu này hướng dẫn cách đóng gói dự án AI Service thành một Docker image duy nhất và expose ra chỉ một cổng 55025. Image này sẽ chứa tất cả các thành phần:

- Scraper: http://localhost:55025/scraper/
- Rewrite: http://localhost:55025/rewrite/
- Keyword Rewrite: http://localhost:55025/keyword-rewrite/
- Facebook Scraper: http://localhost:55025/facebook-scraper/
- Facebook Rewrite: http://localhost:55025/facebook-rewrite/

## Cấu trúc dự án

```
ai_service/
├── Dockerfile          # Dockerfile chính để đóng gói tất cả các dịch vụ
├── nginx/              # Thư mục chứa cấu hình Nginx
│   └── nginx.conf      # Cấu hình Nginx làm reverse proxy
├── start.sh            # Script khởi động tất cả các dịch vụ
├── scraper/            # Service scraper
├── rewrite/            # Service rewrite
├── keyword_rewrite/    # Service keyword_rewrite
├── facebook_scraper/   # Service facebook_scraper
├── facebook_rewrite/   # Service facebook_rewrite
└── .env                # Biến môi trường chung
```

## Biến môi trường

Tất cả các biến môi trường từ file `docker-compose.yml` đã được giữ nguyên và được tích hợp vào file `.env` và script `start.sh`. Các biến này bao gồm:

### Biến chung:
- DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT
- BACKEND_URL, BACKEND_PORT
- OLLAMA_HOST, OLLAMA_BASE_URL, OLLAMA_MODEL

### Biến riêng cho Facebook Scraper:
- FACEBOOK_USERNAME, FACEBOOK_PASSWORD
- USE_CHROME_PROFILE, CHROME_PROFILE_PATH, HEADLESS

### Biến riêng cho Facebook Rewrite:
- API_TIMEOUT, MAX_RETRIES, INITIAL_BACKOFF, MAX_TEXT_SIZE

## Đóng Gói Docker Image

### Chuẩn Bị Môi Trường
1. Cài đặt Docker Desktop hoặc Docker Engine.
2. Đảm bảo môi trường phát triển đã sẵn sàng.

### Xây Dựng Image
Sử dụng file `build-and-save.bat` để xây dựng và lưu Docker image:

## Build Docker Image

```bash
# Di chuyển đến thư mục gốc của dự án
cd ai_service

# Build Docker image
docker build -t ai-service:55025 .
```

## Save Docker Image

```bash
# Lưu Docker image ra file
docker save -o ai-service-55025.tar ai-service:55025
```

## Load Docker Image (ở máy khác)

```bash
# Load Docker image từ file
docker load -i ai-service-55025.tar
```

## Chạy Docker Container

```bash
# Chạy container với cổng 55025
docker run -d --name ai-service-all \
  -p 55025:55025 \
  --add-host=host.docker.internal:host-gateway \
  --add-host=magazine.test:host-gateway \
  ai-service:55025
```

## Kiểm tra các dịch vụ

Sau khi container đã chạy, có thể kiểm tra các dịch vụ qua các URL sau:

- Health check: http://localhost:55025/health
- Danh sách dịch vụ: http://localhost:55025/
- Scraper: http://localhost:55025/scraper/
- Rewrite: http://localhost:55025/rewrite/
- Keyword Rewrite: http://localhost:55025/keyword-rewrite/
- Facebook Scraper: http://localhost:55025/facebook-scraper/
- Facebook Rewrite: http://localhost:55025/facebook-rewrite/

## Xem logs

```bash
# Xem logs tổng quan
docker logs ai-service-all

# Xem logs của từng dịch vụ trong container
docker exec -it ai-service-all cat /var/log/ai_service/scraper.log
docker exec -it ai-service-all cat /var/log/ai_service/rewrite.log
docker exec -it ai-service-all cat /var/log/ai_service/keyword_rewrite.log
docker exec -it ai-service-all cat /var/log/ai_service/facebook_scraper.log
docker exec -it ai-service-all cat /var/log/ai_service/facebook_rewrite.log
```

## Dừng và xóa container

```bash
# Dừng container
docker stop ai-service-all

# Xóa container
docker rm ai-service-all
``` 
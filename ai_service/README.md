# AI Service Deployment

Tài liệu này cung cấp hướng dẫn để triển khai các dịch vụ AI trong hệ thống Magazine AI System.

## Yêu cầu hệ thống

- Docker và Docker Compose
- Backend Laravel đang chạy trên localhost (port 8000)
- XAMPP (MySQL) đang chạy trên localhost (port 3306)
- Ollama đang chạy trên localhost (port 11434)

## Cấu trúc thư mục

```
ai_service/
├── keyword_rewrite/    # Service viết lại từ khóa
├── rewrite/            # Service viết lại nội dung
├── scraper/            # Service thu thập dữ liệu
├── docker-compose.yml  # File cấu hình Docker Compose
└── README.md           # Tài liệu hướng dẫn
```

## Các service được triển khai

1. **Scraper (Port 5001)**: Thu thập dữ liệu từ các nguồn tin tức
2. **Rewrite (Port 5002)**: Viết lại nội dung bài viết
3. **Keyword Rewrite (Port 5003)**: Viết lại từ khóa

## Hướng dẫn triển khai

### 1. Chuẩn bị môi trường

Đảm bảo các dịch vụ sau đã được khởi động trên máy local của bạn:
- Backend Laravel (port 8000)
- XAMPP/MySQL (port 3306)
- Ollama (port 11434)

### 2. Khởi động các dịch vụ AI

Từ thư mục `ai_service`, chạy lệnh sau:

```bash
docker-compose up -d
```

Lệnh này sẽ xây dựng và khởi động tất cả các service trong container Docker.

### 3. Kiểm tra trạng thái

Để kiểm tra trạng thái của các container:

```bash
docker-compose ps
```

### 4. Xem logs

Để xem logs của tất cả các service:

```bash
docker-compose logs
```

Để xem logs của một service cụ thể:

```bash
docker-compose logs scraper
docker-compose logs rewrite
docker-compose logs keyword_rewrite
```

### 5. Dừng các dịch vụ

Để dừng tất cả các dịch vụ:

```bash
docker-compose down
```

## Kết nối đến các dịch vụ

- Scraper API: http://localhost:5001
- Rewrite API: http://localhost:5002
- Keyword Rewrite API: http://localhost:5003

## Xử lý sự cố

1. **Lỗi kết nối đến Backend/MySQL/Ollama**:
   - Đảm bảo các dịch vụ này đang chạy trên máy chủ của bạn
   - Kiểm tra cài đặt tường lửa
   - Xác nhận rằng các cổng (8000, 3306, 11434) đã được mở

2. **Lỗi Docker Container**:
   - Kiểm tra logs để xem lỗi chi tiết
   - Thử dựng lại container bằng cách: `docker-compose down && docker-compose up -d --build`

3. **Vấn đề về quyền truy cập file**:
   - Đảm bảo thư mục `scraper/output` có quyền ghi
   
4. **Lỗi xung đột thư viện Python**:
   - Nếu gặp lỗi "ResolutionImpossible" khi cài đặt các phụ thuộc Python
   - Kiểm tra các file requirements.txt trong từng dịch vụ
   - Tránh chỉ định phiên bản cứng (như numpy==1.24.3), thay vào đó sử dụng phạm vi phiên bản (như numpy>=1.24.3)
   - Có thể chỉnh sửa trực tiếp file trong thư mục service tương ứng và xây dựng lại container

## Ghi chú bổ sung

- Các container sử dụng `host.docker.internal` để kết nối với các dịch vụ trên máy chủ (localhost).
- Dữ liệu được chia sẻ giữa máy chủ và container thông qua volumes.
- Các container được cấu hình để tự động khởi động lại nếu gặp sự cố. 
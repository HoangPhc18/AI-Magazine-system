# AI Keyword Rewrite Service

Dịch vụ AI tự động tìm kiếm, trích xuất và viết lại bài viết từ từ khóa.

## Tính năng

- Tìm kiếm bài viết từ Google News với từ khóa
- Trích xuất nội dung bài viết
- Viết lại nội dung bằng AI (sử dụng mô hình Ollama)
- API để tích hợp với hệ thống khác

## Cài đặt

### Yêu cầu

- Python 3.8+
- Ollama (để chạy mô hình ngôn ngữ)

### Cài đặt thư viện

```bash
pip install flask flask_cors requests python-dotenv beautifulsoup4 trafilatura
```

Hoặc sử dụng file `requirements.txt`:

```bash
pip install -r requirements.txt
```

### Cài đặt Ollama

1. Tải Ollama từ [https://ollama.com/download](https://ollama.com/download)
2. Cài đặt và khởi động Ollama
3. Tải mô hình cơ bản bằng lệnh: `ollama pull gemma2:latest`

## Cấu hình

Tạo file `.env` trong thư mục này với nội dung:

```
PORT=5000
HOST=0.0.0.0
DEBUG=False
OLLAMA_MODEL=gemma2:latest
OLLAMA_HOST=http://localhost:11434
```

## Khởi động dịch vụ

### Windows

**Khởi động tạm thời:**

```
.\start_service.bat
```

**Triển khai dịch vụ (chạy nền):**

```
.\deploy_service.bat
```

### Linux/Mac

**Khởi động tạm thời:**

```
./start_service.sh
```

**Triển khai dịch vụ (chạy daemon):**

```
./deploy_service.sh
```

## API Endpoints

### Health Check

```
GET /health
```

Phản hồi:
```json
{
  "status": "ok",
  "timestamp": "2023-05-01T12:34:56.789",
  "service": "AI Keyword Rewrite",
  "version": "1.0.0",
  "active_tasks": 0
}
```

### Xử lý từ khóa

```
POST /api/keyword_rewrite/process
```

Yêu cầu:
```json
{
  "keyword": "từ khóa cần tìm",
  "rewrite_id": 123,
  "callback_url": "http://example.com/callback"
}
```

Phản hồi:
```json
{
  "status": "processing",
  "message": "Processing keyword: từ khóa cần tìm"
}
```

### Định dạng Callback

Dịch vụ gửi kết quả về URL callback với định dạng sau:

```json
{
  "rewrite_id": 123,
  "status": "completed",
  "source_url": "https://example.com/article",
  "source_title": "Tiêu đề bài viết gốc",
  "source_content": "Nội dung bài viết gốc...",
  "rewritten_content": "Nội dung bài viết đã viết lại..."
}
```

Nếu có lỗi:

```json
{
  "rewrite_id": 123,
  "status": "failed",
  "error_message": "Chi tiết lỗi..."
}
```

## Tích hợp

Dịch vụ này được tích hợp với hệ thống Magazine AI System, cho phép tự động chuyển đổi từ khóa thành bài viết chất lượng.

## Xử lý sự cố

Nếu gặp vấn đề, hãy kiểm tra:

1. File log trong thư mục dịch vụ
2. Đảm bảo Ollama đã được cài đặt và đang chạy
3. Kiểm tra cấu hình trong file `.env`
4. Kiểm tra endpoint `/health` để xác nhận dịch vụ đang hoạt động 
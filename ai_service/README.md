# AI Service

Module AI Service là một hệ thống microservices cho phép xử lý nội dung tự động dựa trên AI. 
Bao gồm các dịch vụ:

- Scraper Service: Thu thập dữ liệu từ các trang web
- Rewrite Service: Viết lại nội dung
- Keyword Rewrite Service: Viết lại nội dụng theo từ khóa
- Facebook Scraper Service: Thu thập dữ liệu từ Facebook
- Facebook Rewrite Service: Viết lại nội dung thu thập từ Facebook

## Cài đặt

Hệ thống được đóng gói hoàn toàn trong Docker, vì vậy chỉ cần cài đặt Docker là có thể chạy.

### Yêu cầu hệ thống

- Docker Desktop (Windows, Mac) hoặc Docker Engine (Linux)
- Ít nhất 4GB RAM cho container
- Ít nhất 10GB dung lượng ổ đĩa trống

### Cách cài đặt

1. Clone repository hoặc giải nén archive vào thư mục trên máy
2. Chạy file `run-container.bat` (Windows) hoặc `start.sh` (Linux/Mac)

## Cấu hình

Các tham số cấu hình được lưu trong file `.env` và có thể được ghi đè bằng biến môi trường khi khởi động container.

### Các biến môi trường quan trọng:

- `BACKEND_URL`: URL của backend API, mặc định là "http://host.docker.internal"
- `DB_HOST`: Hostname của cơ sở dữ liệu, mặc định là "host.docker.internal"
- `OLLAMA_HOST`: URL của Ollama API, mặc định là "http://host.docker.internal:11434"

## Sử dụng API

Tất cả các dịch vụ được expose qua Nginx trên cổng 55025, với các endpoint như sau:

- `http://localhost:55025/` - Trang chủ
- `http://localhost:55025/health` - Kiểm tra trạng thái
- `http://localhost:55025/scraper/` - Scraper API
- `http://localhost:55025/rewrite/` - Rewrite API
- `http://localhost:55025/keyword-rewrite/` - Keyword Rewrite API
- `http://localhost:55025/facebook-scraper/` - Facebook Scraper API
- `http://localhost:55025/facebook-rewrite/` - Facebook Rewrite API

## Kiểm tra lỗi

Nếu có lỗi, bạn có thể kiểm tra logs bằng lệnh:

```
docker logs ai-service-all
```

Hoặc kiểm tra logs của từng dịch vụ trong container:

```
docker exec -it ai-service-all cat /var/log/ai_service/scraper.log
docker exec -it ai-service-all cat /var/log/ai_service/rewrite.log
docker exec -it ai-service-all cat /var/log/ai_service/keyword_rewrite.log
docker exec -it ai-service-all cat /var/log/ai_service/facebook_scraper.log
docker exec -it ai-service-all cat /var/log/ai_service/facebook_rewrite.log
```

## Gỡ lỗi thường gặp

1. **Lỗi "netstat: command not found"**
   - Đã được sửa trong phiên bản hiện tại. Script khởi động hiện đã tự động cài đặt `net-tools` nếu cần.

2. **Lỗi Facebook Scraper Service không khởi động:**
   - Đã sửa lỗi trong `start.sh` để không sử dụng tham số `--serve` khi khởi động `facebook_scraper/main.py`

3. **Lỗi kết nối đến backend:**
   - Kiểm tra cấu hình `BACKEND_URL` trong file `.env` 
   - Đảm bảo backend đang chạy và có thể truy cập từ container Docker

4. **Lỗi kết nối đến cơ sở dữ liệu:**
   - Kiểm tra cấu hình `DB_HOST` trong file `.env`
   - Đảm bảo cơ sở dữ liệu đang chạy và có thể truy cập từ container Docker

5. **Lỗi kết nối đến Ollama:**
   - Kiểm tra cấu hình `OLLAMA_HOST` trong file `.env`
   - Đảm bảo Ollama đang chạy và có thể truy cập từ container Docker

## Cập nhật

Để cập nhật lên phiên bản mới, chạy lại file `run-container.bat` (Windows) hoặc `start.sh` (Linux/Mac).

## Gỡ cài đặt

Để gỡ cài đặt:

1. Dừng container: `docker stop ai-service-all`
2. Xóa container: `docker rm ai-service-all`
3. Xóa image: `docker rmi ai-service:55025`

## Phát triển

Xem file BUILD-README.md để biết thêm chi tiết về cách phát triển và mở rộng hệ thống. 
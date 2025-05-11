# Hướng Dẫn Thiết Lập Môi Trường Cho Magazine AI System

## Giới thiệu

Hệ thống Magazine AI đã được nâng cấp để hỗ trợ tự động phát hiện và cấu hình cho nhiều môi trường khác nhau, bao gồm:
- Windows (sử dụng `host.docker.internal`)
- Linux (sử dụng `172.17.0.1` hoặc địa chỉ IP cấu hình)
- macOS (sử dụng `host.docker.internal`)

Hệ thống cung cấp các script tự động để thiết lập và kiểm tra môi trường, giúp bạn dễ dàng chuyển đổi giữa các hệ điều hành khác nhau.

## Các Script Tự Động

### 1. Script Thiết Lập Môi Trường

#### Trên Linux/macOS:
```bash
./setup-environment.sh
```

#### Trên Windows:
```batch
setup-environment.bat
```

Script này sẽ:
- Tự động phát hiện hệ điều hành bạn đang sử dụng
- Cấu hình lại file `.env` với các địa chỉ IP phù hợp
- Đảm bảo script `run-container.sh`/`run-container.bat` có quyền thực thi
- Kiểm tra trạng thái Docker

### 2. Script Kiểm Tra Cấu Hình

```bash
./check-env-config.sh
```

Script này sẽ:
- Kiểm tra tất cả các file `config.py` trong các module
- Xác minh xem chúng có chức năng phát hiện môi trường không
- Kiểm tra cấu hình `BACKEND_URL` và `DB_HOST` có phù hợp không
- Kiểm tra cấu hình trong file `.env`

## Cấu Hình Tự Động cho Các Module

Các module sau đây đã được cập nhật để tự động phát hiện môi trường:

1. `scraper/config.py`
2. `ai_service/rewrite/config.py`
3. `ai_service/keyword_rewrite/config.py`
4. `ai_service/facebook_scraper/config.py`
5. `ai_service/facebook_rewrite/config.py`

Mỗi module sẽ tự động:
- Phát hiện nếu đang chạy trong Docker container
- Phát hiện hệ điều hành (Linux hoặc Windows)
- Điều chỉnh `BACKEND_URL` và `DB_HOST` phù hợp:
  - Trên Windows: `host.docker.internal`
  - Trên Linux: `172.17.0.1`

## Hướng Dẫn Sử Dụng

### Khi Chuyển từ Windows sang Linux

1. Chạy script thiết lập môi trường:
   ```bash
   ./setup-environment.sh
   ```

2. Kiểm tra cấu hình:
   ```bash
   ./check-env-config.sh
   ```

3. Nếu mọi thứ OK, chạy container:
   ```bash
   ./run-container.sh
   ```

### Khi Chuyển từ Linux sang Windows

1. Chạy script thiết lập môi trường:
   ```batch
   setup-environment.bat
   ```

2. Kiểm tra cấu hình:
   ```bash
   ./check-env-config.sh
   ```

3. Nếu mọi thứ OK, chạy container:
   ```batch
   run-container.bat
   ```

## Cấu Hình Thủ Công (Nếu Cần)

Nếu script tự động không hoạt động, bạn có thể cấu hình thủ công:

1. Chỉnh sửa file `.env` và thay đổi các giá trị sau:
   - `DB_HOST=<host_ip>`
   - `BACKEND_URL=http://<host_ip>`
   - `OLLAMA_HOST=http://<host_ip>:11434`
   - `OLLAMA_BASE_URL=http://<host_ip>:11434`

   Trong đó:
   - Trên Windows: `<host_ip>` là `host.docker.internal`
   - Trên Linux: `<host_ip>` là `172.17.0.1` (hoặc IP của máy host)

2. Đảm bảo `run-container.sh` có quyền thực thi trên Linux:
   ```bash
   chmod +x run-container.sh
   ```

## Xử Lý Sự Cố

### Docker không hoạt động
- Trên Windows: Mở Docker Desktop và đảm bảo nó đang chạy
- Trên Linux: Chạy `sudo systemctl start docker`

### Không thể kết nối tới Backend
- Đảm bảo backend Laravel đang chạy
- Kiểm tra cấu hình `BACKEND_URL` trong file `.env`
- Kiểm tra tường lửa không chặn các cổng cần thiết

### Không thể kết nối tới MySQL
- Đảm bảo MySQL đang chạy
- Kiểm tra cấu hình `DB_HOST` trong file `.env`
- Kiểm tra người dùng MySQL có quyền truy cập từ địa chỉ IP của Docker container

### Không thể kết nối tới Ollama
- Đảm bảo Ollama đang chạy
- Kiểm tra các cấu hình `OLLAMA_HOST` và `OLLAMA_BASE_URL` trong file `.env`
- Đảm bảo cổng 11434 đã được mở

## Hỗ Trợ Thêm

Nếu bạn gặp vấn đề khi thiết lập môi trường, vui lòng liên hệ đội phát triển hoặc mở một issue mới trên hệ thống theo dõi vấn đề. 
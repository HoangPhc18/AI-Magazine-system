# Hướng Dẫn Triển Khai Đa Nền Tảng cho Magazine AI System

## Tổng Quan

Magazine AI System hiện đã được nâng cấp để hoạt động mượt mà trên cả môi trường Windows và Linux. Tài liệu này sẽ hướng dẫn chi tiết về cách thiết lập, cấu hình và vận hành hệ thống trên các nền tảng khác nhau.

## Các Thành Phần Chính

1. **Backend Laravel** - API và giao diện quản trị
2. **MySQL Database** - Lưu trữ dữ liệu
3. **AI Service Container** - Dịch vụ AI chạy trong Docker
4. **Ollama Service** - Mô hình ngôn ngữ lớn chạy cục bộ

## Sự Khác Biệt Giữa Windows và Linux

| Thành phần | Windows | Linux |
|------------|---------|-------|
| Host IP | `host.docker.internal` | `172.17.0.1` |
| Docker Network | Bridge thông qua Docker Desktop | Docker Linux bridge |
| File Path | Sử dụng `\` | Sử dụng `/` |
| Scripts | `.bat` | `.sh` |

## Các Script Tự Động

### 1. Scripts Thiết Lập Môi Trường

- **Linux/macOS**: `ai_service/setup-environment.sh`
- **Windows**: `ai_service/setup-environment.bat` hoặc `ai_service/setup-windows.ps1`

Scripts này sẽ:
- Tự động phát hiện hệ điều hành
- Cấu hình các file `.env` với IP phù hợp
- Thiết lập quyền thực thi cho các script (trên Linux)
- Kiểm tra trạng thái Docker

### 2. Scripts Kiểm Tra Cấu Hình

- **Linux/macOS**: `ai_service/check-env-config.sh`
- **Windows via WSL**: Sử dụng `wsl ./ai_service/check-env-config.sh`

Scripts này sẽ:
- Kiểm tra tất cả các file `config.py` trong các module
- Xác minh chức năng phát hiện môi trường
- Kiểm tra cấu hình IP cho Backend và Database

### 3. Scripts Giám Sát Hệ Thống

- **Linux/macOS**: `ai_service/monitor.sh`
- **Windows**: `ai_service/monitor.ps1`

Scripts này sẽ:
- Kiểm tra kết nối tới Backend API
- Kiểm tra kết nối tới Ollama
- Kiểm tra kết nối tới MySQL
- Kiểm tra trạng thái Docker container
- Hiển thị log gần nhất
- Hướng dẫn xử lý sự cố

## Cách Chuyển Đổi Từ Windows Sang Linux

### Bước 1: Trên Máy Linux Mới

1. Clone repository:
   ```bash
   git clone [repository_url]
   cd magazine-ai-system
   ```

2. Chạy script thiết lập môi trường:
   ```bash
   chmod +x ai_service/setup-environment.sh
   ./ai_service/setup-environment.sh
   ```

3. Kiểm tra cấu hình:
   ```bash
   chmod +x ai_service/check-env-config.sh
   ./ai_service/check-env-config.sh
   ```

4. Thiết lập backend Laravel:
   ```bash
   cd backend
   composer install
   cp .env.example .env
   # Chỉnh sửa file .env theo hướng dẫn
   php artisan migrate
   php artisan serve --host=172.17.0.1
   ```

5. Khởi động container AI service:
   ```bash
   cd ..
   chmod +x run-container.sh
   ./run-container.sh
   ```

### Bước 2: Kiểm Tra Hệ Thống

Sử dụng script giám sát để kiểm tra toàn bộ hệ thống:
```bash
chmod +x ai_service/monitor.sh
./ai_service/monitor.sh
```

## Cách Chuyển Đổi Từ Linux Sang Windows

### Bước 1: Trên Máy Windows Mới

1. Clone repository:
   ```bash
   git clone [repository_url]
   cd magazine-ai-system
   ```

2. Chạy script thiết lập môi trường (PowerShell):
   ```powershell
   .\ai_service\setup-windows.ps1
   ```

3. Thiết lập backend Laravel:
   ```bash
   cd backend
   composer install
   copy .env.example .env
   # Chỉnh sửa file .env theo hướng dẫn
   php artisan migrate
   php artisan serve --host=0.0.0.0
   ```

4. Khởi động container AI service:
   ```bash
   cd ..
   .\run-container.bat
   ```

### Bước 2: Kiểm Tra Hệ Thống

Sử dụng script giám sát để kiểm tra toàn bộ hệ thống:
```powershell
.\ai_service\monitor.ps1
```

## Module Auto-detect

Các module dưới đây đã được cập nhật để tự động phát hiện môi trường và điều chỉnh cấu hình:

1. `scraper/config.py`
2. `ai_service/rewrite/config.py`
3. `ai_service/keyword_rewrite/config.py`
4. `ai_service/facebook_scraper/config.py`
5. `ai_service/facebook_rewrite/config.py`

Mỗi module sẽ tự động:
- Phát hiện nếu đang chạy trong Docker container
- Phát hiện hệ điều hành (Linux hoặc Windows)
- Điều chỉnh `BACKEND_URL` và `DB_HOST` phù hợp

## Xử Lý Sự Cố

### Docker Network

- **Windows**: Đảm bảo Docker Desktop đang chạy và hỗ trợ WSL2
- **Linux**: Kiểm tra Docker bridge network `docker network inspect bridge`

### Backend Laravel

- **Windows**: `php artisan serve --host=0.0.0.0`
- **Linux**: `php artisan serve --host=172.17.0.1`

### MySQL Connection

- **Windows**: Kiểm tra user có quyền kết nối từ `host.docker.internal`
- **Linux**: Kiểm tra user có quyền kết nối từ `172.17.0.1`

### Ollama

- **Windows**: Đảm bảo cổng 11434 không bị chặn
- **Linux**: Kiểm tra service `systemctl status ollama`

## Cấu Trúc Docker

### Windows Setup
```
+-------------------+            +---------------------+
| Windows Host      |            | Docker Container    |
|                   |            |                     |
| Backend Laravel   |<---------->| AI Service          |
| (host.docker.internal)         | (.env configured)   |
|                   |            |                     |
| Ollama Service    |            |                     |
| MySQL             |            |                     |
+-------------------+            +---------------------+
```

### Linux Setup
```
+-------------------+            +---------------------+
| Linux Host        |            | Docker Container    |
|                   |            |                     |
| Backend Laravel   |<---------->| AI Service          |
| (172.17.0.1)      |            | (.env configured)   |
|                   |            |                     |
| Ollama Service    |            |                     |
| MySQL             |            |                     |
+-------------------+            +---------------------+
```

## Thông Tin Hỗ Trợ

Nếu bạn gặp vấn đề khi thiết lập, vui lòng tham khảo:
- Tài liệu `ai_service/ENV-README.md` để biết thêm chi tiết
- Các script giám sát để tự chẩn đoán vấn đề
- Liên hệ đội phát triển nếu cần hỗ trợ thêm

## Lưu Ý Bảo Mật

- Đảm bảo các cổng không bị mở ra Internet
- Sử dụng tường lửa để bảo vệ cổng 11434 (Ollama)
- Thiết lập mật khẩu mạnh cho MySQL
- Sử dụng HTTPS cho backend Laravel khi triển khai production 
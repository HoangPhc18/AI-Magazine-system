# Hướng Dẫn Khắc Phục Lỗi OLLAMA_HOST

## Lỗi Hiện Tại

Hiện tại, khi chạy container AI service, một số module bị lỗi:
- Rewrite Service (Port 5002)
- Facebook Rewrite Service (Port 5005)

Lỗi xuất hiện là `KeyError: 'OLLAMA_HOST'` trong file config.py của các module này.

## Cách Khắc Phục

### Bước 1: Thiết lập môi trường

Trên Windows:
```
ai_service\setup-environment.bat
```

Trên Linux:
```
chmod +x ai_service/setup-environment.sh
./ai_service/setup-environment.sh
```

Script sẽ tự động:
- Phát hiện hệ điều hành của bạn
- Cập nhật file .env với các địa chỉ IP phù hợp
- Đảm bảo cả `OLLAMA_HOST` và `OLLAMA_BASE_URL` được thiết lập đúng

### Bước 2: Sửa định dạng file (Chỉ trên Windows)

Chạy script để đảm bảo các file shell script có định dạng LF đúng:
```
powershell -ExecutionPolicy Bypass -File fix-scripts.ps1
```

### Bước 3: Khởi động lại container

Trên Windows:
```
restart-service.bat
```

Trên Linux:
```
chmod +x restart-service.sh
./restart-service.sh
```

## Kiểm Tra Hệ Thống

Để kiểm tra trạng thái hệ thống:

Trên Windows:
```
powershell -ExecutionPolicy Bypass -File ai_service\monitor.ps1
```

Trên Linux:
```
chmod +x ai_service/monitor.sh
./ai_service/monitor.sh
```

## Nguyên Nhân Lỗi

Lỗi phát sinh do:
1. Biến `OLLAMA_HOST` và `OLLAMA_BASE_URL` có thể không có trong file .env
2. Trong các file config.py, dòng log về `OLLAMA_HOST` được thực thi trước khi biến được khai báo
3. Định dạng file shell script Windows (CRLF) có thể gây vấn đề khi chạy trên Linux

## Cách Thức Hoạt Động

Các scripts sửa lỗi đã:
1. Thêm đoạn code kiểm tra sự tồn tại của biến `OLLAMA_HOST` và `OLLAMA_BASE_URL` trong config
2. Cập nhật script thiết lập môi trường để đảm bảo tất cả các biến được thiết lập đúng
3. Cung cấp công cụ để chuyển đổi các file định dạng Windows thành Linux
4. Cung cấp script để dễ dàng khởi động lại container sau khi cập nhật 
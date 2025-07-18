# Facebook Post Scraper

Tool để thu thập bài viết từ Facebook và lưu vào database Laravel.

## Cài đặt

1. Cài đặt các thư viện Python cần thiết:

```bash
pip install -r requirements.txt
```

2. Đảm bảo đã cài đặt Google Chrome trên máy

3. Trước khi sử dụng, hãy đảm bảo bạn đã đăng nhập vào Facebook trong profile Chrome của bạn

## Chạy dịch vụ API

Để chạy API như một dịch vụ liên tục tiếp nhận các yêu cầu từ backend:

```bash
# Chạy với các tham số mặc định (cổng 5000)
python main.py

# Hoặc chỉ định host/port
python main.py --host 127.0.0.1 --port 5001
```

Để triển khai trên môi trường production, bạn có thể sử dụng Gunicorn:

```bash
gunicorn -w 2 -b 0.0.0.0:5000 "main:app"
```

## API Endpoints

1. **Bắt đầu thu thập bài viết**: `POST /api/scrape`
   - Body request:
     ```json
     {
       "url": "https://www.facebook.com/groups/tên-group",
       "use_profile": true,
       "chrome_profile": "Default", 
       "limit": 10
     }
     ```
   - Response:
     ```json
     {
       "success": true,
       "message": "Đã bắt đầu thu thập dữ liệu",
       "job_id": "job_1"
     }
     ```

2. **Lấy danh sách tất cả jobs**: `GET /api/jobs`

3. **Lấy trạng thái một job cụ thể**: `GET /api/jobs/{job_id}`

4. **Kiểm tra trạng thái dịch vụ**: `GET /health`

## Sử dụng từ CLI (Command Line)

Bạn vẫn có thể sử dụng trực tiếp script `scraper_facebook.py` từ dòng lệnh:

```bash
python scraper_facebook.py --url "https://www.facebook.com/groups/tên-group" --save_to_db true
```

## Tích hợp với Laravel

Để tích hợp với Laravel backend, bạn có thể chọn một trong hai cách:

1. **Gọi trực tiếp script Python**:
   - Đã được cấu hình trong FacebookPostController

2. **Gọi thông qua API**:
   - Gửi request từ backend Laravel đến API của main.py
   - Theo dõi và cập nhật trạng thái job thông qua API

## Lưu ý

- Công cụ này sử dụng Chrome profile hiện có để đăng nhập vào Facebook, không cần nhập username/password
- Đảm bảo rằng file `.env` trong thư mục Laravel backend đã được cấu hình đúng thông tin database
- Trước khi sử dụng, hãy chắc chắn đã chạy migrations trong Laravel để tạo bảng facebook_posts:

```bash
cd backend
php artisan migrate
```

## Xử lý xung đột Chrome

- **Vấn đề**: Selenium không thể sử dụng profile Chrome khi trình duyệt Chrome đang chạy
- **Giải pháp 1**: Đóng Chrome trước khi chạy công cụ này, hoặc chọn "y" khi công cụ hỏi về việc tắt Chrome
- **Giải pháp 2**: Chọn không sử dụng profile Chrome (chọn "n" khi hỏi về việc sử dụng profile)

# Hướng dẫn sử dụng Chrome Profile cho Facebook Scraper

## Giới thiệu

Tài liệu này hướng dẫn cách sử dụng Chrome Profile để đăng nhập Facebook trong Facebook Scraper. Phương pháp này hiệu quả hơn so với sử dụng cookies vì nó lưu trữ toàn bộ thông tin phiên đăng nhập và có thể duy trì đăng nhập lâu hơn.

## Các bước thực hiện

### 1. Chuẩn bị Chrome Profile

1. Mở trình duyệt Chrome trên máy tính của bạn
2. Đăng nhập vào Facebook và đảm bảo đã chọn tùy chọn "Duy trì đăng nhập" hoặc "Remember me"
3. Đóng tất cả các cửa sổ Chrome

### 2. Sao chép Chrome Profile

#### Trên Windows:

Chạy script `copy_chrome_profile.ps1`:

```
cd ai_service/facebook_scraper
powershell -ExecutionPolicy Bypass -File copy_chrome_profile.ps1
```

Script sẽ sao chép các file cần thiết từ Chrome profile của bạn vào thư mục `chrome_profile`.

#### Trên Linux/Mac:

```
mkdir -p ai_service/facebook_scraper/chrome_profile
cp -r ~/.config/google-chrome/Default/* ai_service/facebook_scraper/chrome_profile/
```

(Đường dẫn có thể khác tùy theo hệ điều hành)

### 3. Cấu hình Docker

File `docker-compose.yml` đã được cập nhật để:
- Mount thư mục `chrome_profile` vào container
- Thiết lập biến môi trường `USE_CHROME_PROFILE=true`
- Thiết lập đường dẫn profile với `CHROME_PROFILE_PATH=/app/chrome_profile`

### 4. Khởi động lại container

```
docker-compose restart facebook_scraper
```

### 5. Kiểm tra

Chạy script test để kiểm tra việc đăng nhập:

```
cd ai_service/facebook_scraper
python test_api.py
```

## Xử lý sự cố

Nếu gặp vấn đề:

1. Kiểm tra logs của container:
```
docker-compose logs -f facebook_scraper
```

2. Kiểm tra screenshots trong thư mục `logs`:
```
docker exec -it thp_214476_facebook_scraper ls -la /app/logs
```

3. Đảm bảo quyền truy cập đúng cho thư mục chrome_profile:
```
chmod -R 777 ai_service/facebook_scraper/chrome_profile
```

4. Nếu vẫn gặp vấn đề, thử tắt chế độ headless để xem quá trình đăng nhập:
```
docker-compose exec facebook_scraper bash -c "export HEADLESS=false && python main.py"
```

## Lưu ý

- Chrome profile chứa thông tin đăng nhập của bạn, hãy xử lý cẩn thận
- Facebook có thể yêu cầu xác minh nếu phát hiện đăng nhập từ vị trí mới
- Nếu container chạy trên Linux, có thể cần thêm các thư viện phụ thuộc của Chrome 
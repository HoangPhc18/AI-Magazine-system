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
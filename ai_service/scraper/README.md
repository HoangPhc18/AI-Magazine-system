# News Scraper Module

## Tổng quan
Module này được thiết kế để thu thập tin tức tự động từ Google News dựa trên các danh mục được cấu hình trong hệ thống, sau đó làm giàu nội dung bài viết và gửi dữ liệu đến backend Laravel.

## Cài đặt

1. **Cài đặt các gói phụ thuộc:**
   ```
   pip install -r requirements.txt
   ```

2. **Cấu hình API Key:**
   - Đăng ký tài khoản SerpAPI tại https://serpapi.com/
   - Cập nhật API_KEY trong file `google_news_serpapi.py`

3. **Cấu hình URL Backend:**
   - Đảm bảo URL API trong file cấu hình trỏ đến backend Laravel của bạn
   - Mặc định: `http://localhost:8000/api/articles/import`

## Sử dụng

### Thu thập dữ liệu tin tức
Bước 1: Thu thập dữ liệu từ Google News dựa trên các danh mục

```bash
python google_news_serpapi.py
```

Kết quả:
- Tạo file JSON chứa danh sách bài viết: `scraped_articles_YYYYMMDD_HHMMSS.json`
- Gửi dữ liệu tới backend (tùy chọn)

### Làm giàu nội dung bài viết
Bước 2: Trích xuất nội dung đầy đủ từ nguồn

```bash
python scrape_articles_selenium.py [tên_file_json_từ_bước_1]
```

Nếu không cung cấp tên file, script sẽ tự động tìm file mới nhất được tạo ra từ bước 1.

Kết quả:
- Tạo file JSON chứa nội dung đầy đủ: `enriched_articles_YYYYMMDD_HHMMSS.json`
- Gửi dữ liệu tới backend (tùy chọn)

## Các danh mục
Hệ thống sẽ tự động thu thập tin tức từ các danh mục sau (có thể điều chỉnh trong `google_news_serpapi.py`):

- Công nghệ
- Kinh doanh
- Giải trí
- Thể thao
- Đời sống

## Cấu trúc dữ liệu
Mỗi bài viết thu thập được sẽ có cấu trúc như sau:

```json
{
  "title": "Tiêu đề bài viết",
  "summary": "Tóm tắt nội dung",
  "content": "Nội dung đầy đủ của bài viết",
  "source_name": "Tên nguồn (VD: VnExpress)",
  "source_url": "URL nguồn bài viết",
  "source_icon": "URL icon của nguồn (nếu có)",
  "published_at": "Ngày xuất bản",
  "category": "Danh mục của bài viết",
  "meta_data": {
    "original_source": "google_news",
    "scraped_at": "Thời gian thu thập",
    "extracted_at": "Thời gian trích xuất nội dung",
    "word_count": 1234,
    "position": 1
  }
}
```

## Lưu ý
- Đảm bảo tuân thủ các điều khoản dịch vụ của Google và các trang tin tức khi sử dụng scraper
- Điều chỉnh tham số `max_articles` trong `fetch_articles_by_category()` để thay đổi số lượng bài viết thu thập từ mỗi danh mục
- Sử dụng chức năng `time.sleep()` để tránh gửi quá nhiều request trong thời gian ngắn 

## Chạy Tự Động

Scraper có thể được chạy tự động theo lịch định sẵn. Trong thư mục `batch` có sẵn các script để thực hiện việc này:

### Chạy tức thì

Sử dụng file `batch/run_scraper.bat` để chạy scraper ngay lập tức. Script này sẽ:
- Tự động tìm kiếm bài viết theo danh mục
- Trích xuất nội dung đầy đủ từ các URL
- Tự động gửi dữ liệu đến backend mà không cần xác nhận
- Giữ lại các file tạm thời (không dọn dẹp)

```
batch\run_scraper.bat
```

### Thiết lập chạy theo lịch

Sử dụng file `batch/run_scraper_daily.bat` để chạy scraper theo lịch hàng ngày. Script này sẽ:
- Tự động tìm kiếm bài viết theo danh mục
- Trích xuất nội dung đầy đủ từ các URL
- Tự động gửi dữ liệu đến backend mà không cần xác nhận
- Dọn dẹp các file cũ (giữ lại file trong 7 ngày)
- Hiển thị thông báo khi bắt đầu và kết thúc quá trình

Để thiết lập lịch chạy tự động bằng Task Scheduler trên Windows:

1. Mở Task Scheduler (tìm kiếm "Task Scheduler" trong Start menu)
2. Chọn "Create Basic Task" từ panel bên phải
3. Đặt tên (ví dụ: "AI Magazine Daily Scraper") và mô tả
4. Chọn tần suất chạy (Daily)
5. Thiết lập thời gian chạy (ví dụ: 8:00 AM)
6. Chọn "Start a program"
7. Đường dẫn đến script: Đường dẫn đầy đủ đến file `batch\run_scraper_daily.bat`
8. Hoàn thành thiết lập

Các tham số có thể tùy chỉnh trong file batch:
- `--retention-days X`: Số ngày giữ lại logs và files (mặc định: 7)
- `--batch-size X`: Số lượng bài viết gửi trong mỗi request (mặc định: 5)
- `--verbose`: Hiển thị nhiều thông tin hơn
- `--skip-search`: Bỏ qua bước tìm kiếm bài viết
- `--skip-extraction`: Bỏ qua bước trích xuất nội dung
- `--skip-send`: Bỏ qua bước gửi đến backend 

## Scraper Tin Tức cho AI Magazine

Hệ thống scraper này được thiết kế để thu thập tin tức từ các nguồn trực tuyến, trích xuất nội dung và gửi lên backend của AI Magazine.

### Cấu trúc dự án

```
ai_service/scraper/
├── main.py                  # Script chính điều phối quy trình scraping
├── google_news_serpapi.py   # Thu thập URL bài viết từ Google News
├── scrape_articles_selenium.py  # Trích xuất nội dung từ URL 
├── output/                  # Thư mục chứa các file kết quả và log
├── batch/                   # Chứa các script tự động hóa
│   ├── auto_scraper.bat     # Chạy scraper tự động
│   └── schedule_scraper.bat # Lên lịch chạy tự động hàng ngày
└── requirements.txt         # Danh sách dependencies
```

### Quy trình làm việc

1. **Tìm kiếm URL bài viết**: Thu thập URL từ các danh mục (được lấy từ API backend)
2. **Trích xuất nội dung**: Truy cập URL và trích xuất tiêu đề, nội dung
3. **Gửi dữ liệu tới backend**: Gửi bài viết đã có nội dung đầy đủ tới backend

### Cài đặt

```bash
# Clone repo
git clone <repository-url>

# Cài đặt dependencies
pip install -r requirements.txt
```

### Sử dụng

```bash
# Chạy toàn bộ quy trình
python main.py

# Bỏ qua bước tìm kiếm URL
python main.py --skip-search --input-file path/to/file.json

# Bỏ qua bước trích xuất nội dung
python main.py --skip-extraction

# Bỏ qua bước gửi dữ liệu
python main.py --skip-send

# Chế độ tự động gửi không cần xác nhận
python main.py --auto-send
```

### Thư mục Output

Thư mục `output/` chứa:
- File JSON chứa kết quả tìm kiếm URL (`search_results_*.json`)
- File JSON chứa bài viết đã trích xuất nội dung (`enriched_articles_*.json`)
- File log từ chế độ tự động (`auto_scraper_*.log`)
- File lịch sử chạy tự động (`auto_scraper_history.log`)

Các file cũ sẽ tự động được xóa theo cấu hình giữ lại số ngày nhất định (mặc định 2 ngày, có thể cấu hình với `--retention-days`)

### Tự động hóa với Batch Scripts

#### 1. Chạy tự động thủ công

Script `auto_scraper.bat` sẽ tự động thực hiện toàn bộ quy trình scraping và lưu kết quả vào thư mục `output/`.

```
# Chạy thủ công
.\batch\auto_scraper.bat
```

#### 2. Lên lịch chạy tự động

Script `schedule_scraper.bat` sẽ thiết lập để chạy tự động `auto_scraper.bat` hàng ngày vào lúc 2:00 sáng.

```
# Lên lịch chạy tự động (yêu cầu quyền quản trị)
.\batch\schedule_scraper.bat
```

### Các tùy chọn nâng cao

```
usage: main.py [-h] [--skip-search] [--skip-extraction] [--skip-send]
               [--input-file INPUT_FILE] [--auto-send]
               [--batch-size BATCH_SIZE] [--verbose]
               [--retention-days RETENTION_DAYS] [--no-cleanup]

options:
  -h, --help            Hiển thị trợ giúp và thoát
  --skip-search         Bỏ qua bước tìm kiếm URL bài viết
  --skip-extraction     Bỏ qua bước trích xuất nội dung
  --skip-send           Bỏ qua bước gửi đến backend
  --input-file INPUT_FILE
                        File JSON chứa bài viết để xử lý
  --auto-send           Tự động gửi bài viết không cần xác nhận
  --batch-size BATCH_SIZE
                        Số lượng bài viết gửi trong mỗi request
  --verbose             Hiển thị nhiều thông tin hơn
  --retention-days RETENTION_DAYS
                        Số ngày giữ lại files trước khi xóa
  --no-cleanup          Không xóa files cũ
```

### Các lưu ý

- Cần đảm bảo backend API đang chạy (mặc định: http://localhost:8000)
- Các danh mục được lấy từ API backend, nếu không kết nối được sẽ sử dụng danh mục mặc định
- Khi admin thêm, sửa, xóa danh mục trong hệ thống, script sẽ tự động cập nhật theo
- Để thay đổi lịch chạy tự động, chạy lại `schedule_scraper.bat` với quyền quản trị 
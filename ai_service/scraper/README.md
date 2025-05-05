# Hệ thống thu thập tin tức tự động

Hệ thống scraper tự động thu thập tin tức từ nhiều nguồn khác nhau:
1. Google News (thu thập qua SerpAPI hoặc trực tiếp)
2. WorldNewsAPI (https://worldnewsapi.com)
3. Currents API (https://currentsapi.services/en)

## Tính năng

- Thu thập tin tức từ nhiều nguồn API
- Tìm kiếm bài viết theo danh mục
- Tìm kiếm bài viết theo danh mục con
- Trích xuất nội dung đầy đủ từ các URL bài viết
- Lưu trữ kết quả trong thư mục `output`
- Gửi bài viết đến backend API
- Tự động hóa quá trình bằng các tập lệnh batch
- Dọn dẹp file tự động để tiết kiệm dung lượng

## Cài đặt

1. Clone repository 
2. Cài đặt các thư viện phụ thuộc:
   ```
   pip install -r requirements.txt
   ```
3. Thiết lập API keys (xem phần bên dưới)

## Thiết lập API keys

Để sử dụng đầy đủ các nguồn API, bạn cần thiết lập các API key trong file `.env`:

1. Tạo file `.env` trong thư mục `ai_service/scraper/`:
   ```
   touch .env
   ```

2. Thêm các API key vào file `.env`:
   ```
   # API Keys
   WORLDNEWS_API_KEY=your_worldnews_api_key_here
   CURRENTS_API_KEY=your_currents_api_key_here
   ```

### Cách lấy API keys:

1. **WorldNewsAPI**:
   - Đăng ký tài khoản tại https://worldnewsapi.com
   - Copy API key từ dashboard và thêm vào file `.env`

2. **Currents API**:
   - Đăng ký tài khoản tại https://currentsapi.services/en/register
   - Copy API key từ trang tài khoản và thêm vào file `.env`

## Cấu trúc thư mục

```
ai_service/
└── scraper/
    ├── main.py               # Script chính điều phối quá trình thu thập
    ├── google_news_serpapi.py # Thu thập từ Google News qua SerpAPI
    ├── worldnews_api.py      # Thu thập từ WorldNewsAPI
    ├── currents_api.py       # Thu thập từ Currents API
    ├── scrape_articles_selenium.py # Trích xuất nội dung bài viết
    ├── .env                  # File chứa API keys (không đưa vào git)
    ├── output/               # Thư mục lưu trữ kết quả
    └── batch/                # Các tập lệnh tự động hóa
        ├── auto_scraper.bat  # Tự động hóa quy trình thu thập
        └── schedule_scraper.bat # Lập lịch tự động thu thập
```

## Sử dụng

### Thu thập bài viết

```
python main.py
```

### Tùy chọn dòng lệnh:

```
python main.py --help
```

Các tùy chọn:
- `--all`: Xử lý tất cả các danh mục
- `--category ID`: Xử lý danh mục với ID được chỉ định
- `--subcategory ID`: Xử lý danh mục con với ID được chỉ định (kết hợp với --category)
- `--keyword TEXT`: Tìm kiếm bài viết với từ khóa cụ thể
- `--import-dir DIR`: Thư mục chứa file JSON cần import
- `--import-file FILE`: File JSON cần import
- `--auto-send`: Tự động gửi bài viết không cần xác nhận
- `--use-subcategories`: Sử dụng danh mục con khi tìm kiếm (mặc định khi dùng --all)
- `--skip-search`: Bỏ qua bước tìm kiếm URL bài viết
- `--skip-extraction`: Bỏ qua bước trích xuất nội dung
- `--skip-send`: Bỏ qua bước gửi đến backend
- `--input-file FILE`: File JSON chứa bài viết để xử lý
- `--batch-size N`: Số lượng bài viết gửi trong mỗi request (mặc định: 5)
- `--verbose`: Hiển thị nhiều thông tin hơn
- `--retention-days N`: Số ngày giữ lại files trước khi xóa (mặc định: 7)
- `--no-cleanup`: Không xóa files cũ
- `--output-dir DIR`: Thư mục lưu kết quả (mặc định: ./output)

### API Endpoints

Hệ thống cung cấp các API endpoints thông qua Flask:

- **GET /health**: Kiểm tra trạng thái API
- **POST /reload-config**: Tải lại cấu hình từ file .env
- **POST /run**: Kích hoạt quá trình scraping với các tham số tùy chọn
  - Tham số JSON: `category_id` (tùy chọn), `subcategory_id` (tùy chọn)
- **POST /scrape-subcategory**: Kích hoạt scraping cho một danh mục con cụ thể
  - Tham số JSON: `category_id` (bắt buộc), `subcategory_id` (bắt buộc)

### Ví dụ sử dụng API:

```bash
# Scrape tất cả danh mục
curl -X POST http://localhost:5001/run

# Scrape một danh mục cụ thể
curl -X POST http://localhost:5001/run -H "Content-Type: application/json" -d '{"category_id": 5}'

# Scrape một danh mục con cụ thể
curl -X POST http://localhost:5001/scrape-subcategory -H "Content-Type: application/json" -d '{"category_id": 5, "subcategory_id": 10}'
```

### Tự động hóa

Bạn có thể sử dụng các tập lệnh batch để tự động hóa quy trình:

1. **Chạy thu thập tự động**:
   ```
   cd ai_service/scraper/batch
   auto_scraper.bat
   ```

2. **Lập lịch thu thập tự động**:
   ```
   cd ai_service/scraper/batch
   schedule_scraper.bat
   ```

## Testing các nguồn API riêng lẻ

Bạn có thể kiểm tra từng nguồn API riêng lẻ:

```
python worldnews_api.py
python currents_api.py
```

## Lưu ý

- Đảm bảo file `.env` chứa các API key hợp lệ
- KHÔNG đưa file `.env` vào git repository vì lý do bảo mật
- Thêm `.env` vào file `.gitignore` để tránh vô tình đưa lên git
- Kiểm tra thư mục `output` để xem kết quả thu thập
- Hệ thống sẽ tự động dọn dẹp các file cũ sau một số ngày (mặc định: 7 ngày)
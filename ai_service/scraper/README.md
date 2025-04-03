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
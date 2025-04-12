# Dịch vụ viết lại nội dung bằng AI

Module này cung cấp các công cụ để viết lại nội dung bài viết sử dụng các mô hình AI khác nhau như OpenAI API hoặc Ollama local.

## Tính năng

- Hỗ trợ nhiều nhà cung cấp mô hình AI:
  - OpenAI API (GPT-3.5, GPT-4, v.v.)
  - Ollama (gemma2, llama, mistral, v.v.)
- Tự động chuyển đổi giữa các nhà cung cấp dựa trên tính khả dụng
- Cấu hình linh hoạt cho mỗi nhà cung cấp
- Giao diện dòng lệnh (CLI) đầy đủ
- API đơn giản để tích hợp vào các ứng dụng khác
- Hỗ trợ prompt tùy chỉnh
- **Tích hợp cơ sở dữ liệu**: Tự động lấy bài viết từ bảng `articles`, viết lại và lưu vào bảng `rewritten_articles`
- **Giới hạn độ dài**: Tự động viết lại nội dung thành đoạn văn ngắn gọn chỉ khoảng 50-100 từ

## Cài đặt

### Yêu cầu

- Python 3.8+
- Các thư viện phụ thuộc (xem [requirements.txt](requirements.txt))
- MySQL/MariaDB (cho tích hợp cơ sở dữ liệu)

### Cài đặt từ source

```bash
# Cài đặt các phụ thuộc
pip install -r requirements.txt
```

## Cấu hình

### Biến môi trường

Tạo file `.env` hoặc thiết lập biến môi trường (xem [.env.example](.env.example)):

```
OPENAI_API_KEY=your_openai_api_key
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=magazine
```

### Ollama

Để sử dụng Ollama, cần cài đặt Ollama trên máy tính và tải mô hình `gemma2`:

```bash
# Tải và cài đặt Ollama từ: https://ollama.ai/
# Sau đó tải mô hình
ollama pull gemma2:latest
```

### Cấu trúc cơ sở dữ liệu

Dịch vụ yêu cầu các bảng sau trong cơ sở dữ liệu:

- `articles`: Chứa bài viết gốc cần viết lại
- `categories`: Chứa thông tin về danh mục
- `rewritten_articles`: Chứa bài viết đã viết lại

Cấu trúc bảng `rewritten_articles`:

```sql
CREATE TABLE `rewritten_articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `original_content` text COLLATE utf8mb4_unicode_ci,
  `rewritten_content` text COLLATE utf8mb4_unicode_ci,
  `similarity_score` float DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rewritten_article_id` (`article_id`),
  CONSTRAINT `fk_rewritten_article_id` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Sử dụng

### Viết lại bài viết từ cơ sở dữ liệu

```bash
# Viết lại 5 bài viết mới nhất
python -m rewrite.run_rewriter

# Chỉ định nhà cung cấp (sử dụng Ollama)
python -m rewrite.run_rewriter --provider ollama

# Chế độ xem trước (không lưu vào cơ sở dữ liệu)
python -m rewrite.run_rewriter --dry-run

# Viết lại bài viết cho một danh mục cụ thể
python -m rewrite.run_rewriter --category 2

# Sử dụng prompt tùy chỉnh
python -m rewrite.run_rewriter --prompt "Hãy viết lại bài báo này với phong cách hài hước, tóm tắt trong khoảng 50-100 từ:"

# Xem thêm tùy chọn
python -m rewrite.run_rewriter --help
```

### Sử dụng dòng lệnh

```bash
# Viết lại nội dung với OpenAI
python -m rewrite.cli rewrite --text "Nội dung cần viết lại"

# Viết lại nội dung từ file với Ollama
python -m rewrite.cli --provider ollama rewrite --file input.txt --output output.txt

# Hiển thị thông tin
python -m rewrite.cli info

# Quản lý cấu hình
python -m rewrite.cli config --show
python -m rewrite.cli config --set openai.model gpt-4
python -m rewrite.cli config --reset
```

### Sử dụng API

#### Cách đơn giản nhất

```python
from rewrite import rewrite_content

original_content = "Nội dung cần viết lại..."
rewritten = rewrite_content(original_content, provider="openai")
print(rewritten)
```

#### Sử dụng RewriteService

```python
from rewrite import RewriteService

# Tạo đối tượng dịch vụ
service = RewriteService()

# Chuyển đổi nhà cung cấp nếu cần
service.switch_provider("ollama")

# Viết lại nội dung
original_content = "Nội dung cần viết lại..."
rewritten = service.rewrite(original_content)
print(rewritten)

# Sử dụng prompt tùy chỉnh
custom_prompt = "Hãy viết lại với phong cách sôi động:"
rewritten_with_prompt = service.rewrite(original_content, custom_prompt)
print(rewritten_with_prompt)
```

#### Tích hợp cơ sở dữ liệu

```python
from rewrite.db_integration import process_articles

# Viết lại 5 bài viết mới nhất từ cơ sở dữ liệu
results = process_articles(
    provider="openai",  # hoặc "ollama"
    limit=5,
    category_id=None,  # Lọc theo danh mục nếu cần
    custom_prompt=None,  # Prompt tùy chỉnh
    dry_run=False  # Đặt thành True để xem trước mà không lưu
)

# Hiển thị kết quả
for result in results:
    if result["success"]:
        print(f"Đã viết lại bài viết: {result['title']}")
        print(f"Độ dài gốc: {result['original_length']}")
        print(f"Độ dài mới: {result['rewritten_length']}")
    else:
        print(f"Lỗi khi viết lại: {result['error']}")
```

## Sử dụng Script Viết Lại Nội Dung Từ Database

Script `rewrite_from_db.py` cho phép tự động lấy các bài viết từ database, dùng AI để viết lại theo định dạng ngắn gọn, sau đó lưu trở lại vào database.

### Cách sử dụng:

1. **Chuẩn bị môi trường:**
   - Đảm bảo đã cài đặt các thư viện cần thiết: `pip install mysql-connector-python python-dotenv requests`
   - Cấu hình file `.env` với thông tin kết nối database và API Ollama:
     ```
     DB_HOST=localhost
     DB_USER=root
     DB_PASSWORD=yourpassword
     DB_NAME=aimagazinedb
     DB_PORT=3306
     OLLAMA_BASE_URL=http://localhost:11434
     OLLAMA_MODEL=gemma2
     ```

2. **Chạy script thủ công:**
   ```bash
   python rewrite_from_db.py
   ```
   
   Script sẽ tự động:
   - Kết nối đến database
   - Lấy 2 bài viết chưa được viết lại
   - Sử dụng Gemma2 để viết lại nội dung thành đoạn văn ngắn gọn (50-100 từ)
   - Lưu nội dung đã viết lại vào bảng `rewritten_articles`
   - Xóa bài viết gốc từ bảng `articles`

3. **Chạy tự động với tham số:**
   ```bash
   python rewrite_from_db.py --auto --limit 5 --delete --log rewriter.log
   ```
   
   Các tham số:
   - `--auto`: Chạy ở chế độ tự động không cần tương tác người dùng
   - `--limit N`: Giới hạn số bài viết xử lý (mặc định: 3, tối đa: 3)
   - `--delete`: Tự động xóa bài viết gốc sau khi viết lại
   - `--log FILE`: Chỉ định file log (mặc định: rewriter.log)
   - `--ids IDs`: Viết lại các bài viết theo ID cụ thể (ví dụ: "1,2,3")

4. **Ví dụ sử dụng:**
   ```bash
   # Viết lại tối đa 3 bài viết chưa được xử lý mới nhất
   python rewrite_from_db.py --auto
   
   # Viết lại các bài viết cụ thể theo ID
   python rewrite_from_db.py --ids "123,456,789"
   
   # Viết lại tối đa 2 bài viết và xóa bài gốc
   python rewrite_from_db.py --limit 2 --delete
   ```

5. **Thiết lập Cron Job (Linux/macOS):**
   ```
   # Chạy mỗi 30 phút
   */30 * * * * cd /đường/dẫn/đến/ai_service/rewrite && python rewrite_from_db.py --auto --delete
   ```

6. **Thiết lập Task Scheduler (Windows):**
   - Tạo file batch (ví dụ: `run_rewriter.bat`):
     ```batch
     cd C:\đường\dẫn\đến\ai_service\rewrite
     python rewrite_from_db.py --auto --delete
     ```

### Lưu ý:

- Script yêu cầu kết nối đến máy chủ Ollama đang chạy Gemma2, hoặc có thể cấu hình để sử dụng model khác
- Dữ liệu đã viết lại sẽ được lưu trong bảng `rewritten_articles` với tham chiếu đến ID của bài viết gốc
- Mỗi lần chạy, script sẽ tạo file log ghi lại quá trình xử lý

## Ví dụ

Xem file [example.py](example.py) để biết thêm ví dụ về cách sử dụng API cơ bản.

## Lưu ý

- Dịch vụ này sẽ tự động bỏ qua các bài viết đã được viết lại trước đó (đã có trong bảng `rewritten_articles`).
- Mỗi lần viết lại, metadata được lưu lại bao gồm: thời gian xử lý, nhà cung cấp, mô hình, độ dài ban đầu và mới.
- Mặc định, tìm kiếm các bài viết chưa được viết lại theo thứ tự từ mới đến cũ.

## Giấy phép

[MIT License](LICENSE) 
# AI Service for Magazine

Service thu thập tin tức và viết lại nội dung bằng AI.

## Cài đặt

1. Tạo môi trường ảo:
```bash
python -m venv venv
```

2. Kích hoạt môi trường ảo:
- Windows:
```bash
venv\Scripts\activate
```

3. Cài đặt các thư viện:
```bash
pip install -r requirements.txt
```

4. Tạo file .env và cấu hình các biến môi trường:
```
SERPAPI_KEY=your_serpapi_key
WORLDNEWS_API_KEY=your_worldnews_api_key
CURRENTS_API_KEY=your_currents_api_key
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=AiMagazineDB
```

## Cấu trúc thư mục

```
ai_service/
├── venv/                  # Môi trường ảo Python
├── config/               # Cấu hình
│   └── config.py         # Cấu hình chung
├── services/             # Các service
│   ├── news_collector.py # Thu thập tin tức
│   └── content_rewriter.py # Viết lại nội dung
├── utils/               # Tiện ích
│   ├── db.py           # Kết nối database
│   └── helpers.py      # Các hàm tiện ích
├── .env                # Biến môi trường
├── requirements.txt    # Các thư viện cần thiết
└── main.py            # File chính
```

## Sử dụng

1. Thu thập tin tức:
```bash
python main.py collect
```

2. Viết lại nội dung:
```bash
python main.py rewrite
```

3. Chạy tất cả:
```bash
python main.py all
``` 
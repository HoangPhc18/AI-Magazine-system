"""
Mô-đun thu thập dữ liệu tin tức từ WorldNewsAPI
Lấy các bài viết tiếng Việt theo danh mục

Yêu cầu:
- API key từ WorldNewsAPI (https://worldnewsapi.com)
- requests

Cách sử dụng:
1. Đặt API key vào biến môi trường WORLDNEWS_API_KEY
2. Gọi hàm fetch_articles_by_category(category) để lấy bài viết theo danh mục
"""

import os
import requests
import json
import logging
import time
from datetime import datetime
from urllib.parse import urlparse
import random
from dotenv import load_dotenv

# Tải biến môi trường từ file .env
load_dotenv()

# Thiết lập logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger('worldnews_api')

# API key - lấy từ biến môi trường
API_KEY = os.environ.get('WORLDNEWS_API_KEY', '')

# API endpoints
SEARCH_NEWS_URL = "https://api.worldnewsapi.com/search-news"

# Danh sách User-Agents để luân phiên
USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36 Edg/113.0.1774.42"
]

# Số bài viết tối đa cho mỗi danh mục - giới hạn 1 bài viết/danh mục
MAX_ARTICLES_PER_CATEGORY = 1

# Bảng ánh xạ danh mục tiếng Việt sang từ khóa tìm kiếm tiếng Anh
CATEGORY_MAPPING = {
    "Chính trị": "politics vietnam",
    "Kinh tế": "economics vietnam",
    "Xã hội": "society vietnam",
    "Pháp luật": "law vietnam",
    "Thế giới": "world news vietnam",
    "Văn hóa": "culture vietnam",
    "Giáo dục": "education vietnam",
    "Y tế": "health vietnam",
    "Khoa học": "science vietnam",
    "Công nghệ": "technology vietnam",
    "Thể thao": "sports vietnam",
    "Giải trí": "entertainment vietnam"
}


def search_news(category, max_count=MAX_ARTICLES_PER_CATEGORY):
    """
    Tìm kiếm tin tức từ WorldNewsAPI
    
    Args:
        category (str): Danh mục tin tức
        max_count (int): Số lượng bài viết tối đa - mặc định 1 bài viết
        
    Returns:
        list: Danh sách bài viết (tối đa 1 bài viết)
    """
    if not API_KEY:
        logger.error("API key cho WorldNewsAPI không được thiết lập. Hãy thiết lập biến môi trường WORLDNEWS_API_KEY")
        return []
    
    # Đảm bảo max_count luôn là 1
    max_count = 1
    
    # Lấy từ khóa tìm kiếm từ bảng ánh xạ hoặc sử dụng danh mục gốc
    search_keyword = CATEGORY_MAPPING.get(category, f"{category} vietnam")
    
    # Tạo headers với User-Agent ngẫu nhiên
    headers = {
        "User-Agent": random.choice(USER_AGENTS),
        "x-api-key": API_KEY
    }
    
    # Thiết lập tham số tìm kiếm
    params = {
        "text": search_keyword,
        "language": "vi",  # Ngôn ngữ tiếng Việt
        "number": max_count,
        "sort": "publish-time",
        "sort-direction": "desc",
        "offset": 0
    }
    
    try:
        logger.info(f"Tìm kiếm 1 bài viết cho danh mục '{category}' với từ khóa '{search_keyword}'")
        response = requests.get(SEARCH_NEWS_URL, headers=headers, params=params)
        
        # Kiểm tra response status
        if response.status_code == 200:
            data = response.json()
            news_articles = data.get("news", [])
            
            if news_articles:
                # Đảm bảo chỉ lấy 1 bài viết
                news_article = news_articles[0:1]
                logger.info(f"Đã lấy 1 bài viết cho danh mục '{category}'")
                return process_articles(news_article, category)
            else:
                logger.warning(f"Không tìm thấy bài viết nào cho danh mục '{category}'")
                return []
        else:
            logger.error(f"Lỗi khi tìm kiếm tin tức: {response.status_code} - {response.text}")
            return []
    
    except Exception as e:
        logger.error(f"Lỗi khi tìm kiếm tin tức từ WorldNewsAPI: {str(e)}")
        return []


def process_articles(articles, category):
    """
    Xử lý và chuyển đổi định dạng bài viết từ WorldNewsAPI sang định dạng chung
    
    Args:
        articles (list): Danh sách bài viết từ WorldNewsAPI
        category (str): Danh mục tin tức
        
    Returns:
        list: Danh sách bài viết đã được chuyển đổi
    """
    processed_articles = []
    
    for index, article in enumerate(articles):
        try:
            # Trích xuất thông tin từ API response
            title = article.get("title", "")
            url = article.get("url", "")
            
            if not url or not title:
                continue
            
            # Trích xuất domain từ URL
            domain = urlparse(url).netloc
            source_name = domain.replace("www.", "").split(".")[0].capitalize()
            
            # Tạo đối tượng bài viết
            processed_article = {
                "title": title,
                "summary": article.get("summary", f"Bài viết liên quan đến {category}"),
                "content": article.get("text", None),  # Một số API có thể trả về nội dung đầy đủ
                "source_name": source_name,
                "source_url": url,
                "source_icon": f"https://{domain}/favicon.ico",
                "published_at": article.get("publish_date", datetime.now().strftime("%Y-%m-%dT%H:%M:%SZ")),
                "category": category,
                "meta_data": {
                    "original_source": "worldnews_api",
                    "scraped_at": datetime.now().isoformat(),
                    "position": index + 1,
                    "author": article.get("author", ""),
                    "image": article.get("image", "")
                }
            }
            
            processed_articles.append(processed_article)
        
        except Exception as e:
            logger.error(f"Lỗi khi xử lý bài viết: {str(e)}")
    
    return processed_articles


def fetch_articles_by_category(category):
    """
    Tìm kiếm bài viết cho một danh mục cụ thể
    
    Args:
        category (str): Tên danh mục cần tìm kiếm
        
    Returns:
        list: Danh sách bài viết
    """
    logger.info(f"Tìm kiếm bài viết cho danh mục: {category} từ WorldNewsAPI")
    
    try:
        # Tìm kiếm bài viết
        articles = search_news(category)
        
        if articles:
            logger.info(f"Tìm thấy {len(articles)} bài viết cho danh mục '{category}' từ WorldNewsAPI")
            return articles
        else:
            logger.warning(f"Không tìm thấy bài viết nào cho danh mục '{category}' từ WorldNewsAPI")
            return []
    
    except Exception as e:
        logger.error(f"Lỗi khi tìm kiếm bài viết từ WorldNewsAPI cho danh mục {category}: {str(e)}")
        return []


def main():
    """
    Hàm chính để kiểm tra chức năng
    """
    if not API_KEY:
        print("Vui lòng thiết lập API key cho WorldNewsAPI qua biến môi trường WORLDNEWS_API_KEY")
        return
    
    # Thử nghiệm với một số danh mục
    test_categories = ["Công nghệ", "Kinh tế", "Thể thao"]
    
    for category in test_categories:
        print(f"\n=== Tìm kiếm bài viết cho danh mục: {category} ===")
        articles = fetch_articles_by_category(category)
        
        if articles:
            print(f"Tìm thấy {len(articles)} bài viết:")
            for i, article in enumerate(articles, 1):
                print(f"{i}. {article['title']} - {article['source_url']}")
        else:
            print(f"Không tìm thấy bài viết nào cho danh mục {category}")
        
        # Tránh gửi quá nhiều request liên tiếp
        if category != test_categories[-1]:
            print("Đợi 2 giây...")
            time.sleep(2)


if __name__ == "__main__":
    main() 
"""
Mô-đun thu thập dữ liệu tin tức từ Currents API
Lấy các bài viết tiếng Việt theo danh mục

Yêu cầu:
- API key từ Currents API (https://currentsapi.services/en)
- requests

Cách sử dụng:
1. Đặt API key vào biến môi trường CURRENTS_API_KEY
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
logger = logging.getLogger('currents_api')

# API key - lấy từ biến môi trường
API_KEY = os.environ.get('CURRENTS_API_KEY', '')

# API endpoints
SEARCH_NEWS_URL = "https://api.currentsapi.services/v1/search"
LATEST_NEWS_URL = "https://api.currentsapi.services/v1/latest-news"

# Danh sách User-Agents để luân phiên
USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36 Edg/113.0.1774.42"
]

# Số bài viết tối đa cho mỗi danh mục - giới hạn 1 bài viết/danh mục
MAX_ARTICLES_PER_CATEGORY = 1

# Bảng ánh xạ danh mục tiếng Việt sang từ khóa tìm kiếm tiếng Anh và danh mục của Currents API
CATEGORY_MAPPING = {
    "Chính trị": {"keywords": "chính trị việt nam politics vietnam", "category": "politics"},
    "Kinh tế": {"keywords": "kinh tế việt nam economics vietnam", "category": "business"},
    "Xã hội": {"keywords": "xã hội việt nam society vietnam", "category": "regional"},
    "Pháp luật": {"keywords": "pháp luật việt nam law vietnam", "category": "politics"},
    "Thế giới": {"keywords": "tin thế giới world news", "category": "world"},
    "Văn hóa": {"keywords": "văn hóa việt nam culture vietnam", "category": "regional"},
    "Giáo dục": {"keywords": "giáo dục việt nam education vietnam", "category": "regional"},
    "Y tế": {"keywords": "y tế việt nam health vietnam", "category": "health"},
    "Khoa học": {"keywords": "khoa học việt nam science vietnam", "category": "science"},
    "Công nghệ": {"keywords": "công nghệ việt nam technology vietnam", "category": "technology"},
    "Thể thao": {"keywords": "thể thao việt nam sports vietnam", "category": "sports"},
    "Giải trí": {"keywords": "giải trí việt nam entertainment vietnam", "category": "entertainment"}
}


def search_news(category, max_count=MAX_ARTICLES_PER_CATEGORY):
    """
    Tìm kiếm tin tức từ Currents API
    
    Args:
        category (str): Danh mục tin tức
        max_count (int): Số lượng bài viết tối đa - mặc định 1 bài viết
        
    Returns:
        list: Danh sách bài viết (tối đa 1 bài viết)
    """
    if not API_KEY:
        logger.error("API key cho Currents API không được thiết lập. Hãy thiết lập biến môi trường CURRENTS_API_KEY")
        return []
    
    # Đảm bảo max_count luôn là 1
    max_count = 1
    
    # Lấy từ khóa tìm kiếm và danh mục từ bảng ánh xạ
    mapping = CATEGORY_MAPPING.get(category, {"keywords": f"{category} vietnam", "category": "general"})
    search_keyword = mapping["keywords"]
    api_category = mapping["category"]
    
    # Tạo headers với User-Agent ngẫu nhiên
    headers = {
        "User-Agent": random.choice(USER_AGENTS)
    }
    
    # Thiết lập tham số tìm kiếm
    params = {
        "apiKey": API_KEY,
        "keywords": search_keyword,
        "language": "vi",  # Ngôn ngữ tiếng Việt
        "category": api_category,
        "country": "VN",  # Quốc gia Việt Nam
        "page_size": max_count
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
                # Thử tìm kiếm tin tức mới nhất nếu không tìm thấy qua từ khóa
                return get_latest_news(category, api_category, max_count)
        else:
            logger.error(f"Lỗi khi tìm kiếm tin tức: {response.status_code} - {response.text}")
            return []
    
    except Exception as e:
        logger.error(f"Lỗi khi tìm kiếm tin tức từ Currents API: {str(e)}")
        return []


def get_latest_news(category, api_category="general", max_count=MAX_ARTICLES_PER_CATEGORY):
    """
    Lấy tin tức mới nhất từ Currents API
    
    Args:
        category (str): Danh mục tin tức (cho mục đích ghi nhãn)
        api_category (str): Danh mục API
        max_count (int): Số lượng bài viết tối đa - mặc định 1 bài viết
        
    Returns:
        list: Danh sách bài viết (tối đa 1 bài viết)
    """
    if not API_KEY:
        return []
    
    # Đảm bảo max_count luôn là 1
    max_count = 1
    
    # Tạo headers với User-Agent ngẫu nhiên
    headers = {
        "User-Agent": random.choice(USER_AGENTS)
    }
    
    # Thiết lập tham số
    params = {
        "apiKey": API_KEY,
        "language": "vi",  # Ngôn ngữ tiếng Việt
        "category": api_category,
        "country": "VN",  # Quốc gia Việt Nam
        "page_size": max_count
    }
    
    try:
        logger.info(f"Lấy 1 tin tức mới nhất cho danh mục '{category}'")
        response = requests.get(LATEST_NEWS_URL, headers=headers, params=params)
        
        # Kiểm tra response status
        if response.status_code == 200:
            data = response.json()
            news_articles = data.get("news", [])
            
            if news_articles:
                # Đảm bảo chỉ lấy 1 bài viết
                news_article = news_articles[0:1]
                logger.info(f"Đã lấy 1 tin tức mới nhất cho danh mục '{category}'")
                return process_articles(news_article, category)
            else:
                logger.warning(f"Không tìm thấy tin tức mới nhất nào cho danh mục '{category}'")
                return []
        else:
            logger.error(f"Lỗi khi lấy tin tức mới nhất: {response.status_code} - {response.text}")
            return []
    
    except Exception as e:
        logger.error(f"Lỗi khi lấy tin tức mới nhất từ Currents API: {str(e)}")
        return []


def process_articles(articles, category):
    """
    Xử lý và chuyển đổi định dạng bài viết từ Currents API sang định dạng chung
    
    Args:
        articles (list): Danh sách bài viết từ Currents API
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
            source_name = article.get("author", domain.replace("www.", "").split(".")[0].capitalize())
            
            # Tạo đối tượng bài viết
            processed_article = {
                "title": title,
                "summary": article.get("description", f"Bài viết liên quan đến {category}"),
                "content": None,  # Currents API không trả về nội dung đầy đủ
                "source_name": source_name,
                "source_url": url,
                "source_icon": f"https://{domain}/favicon.ico",
                "published_at": article.get("published", datetime.now().strftime("%Y-%m-%dT%H:%M:%SZ")),
                "category": category,
                "meta_data": {
                    "original_source": "currents_api",
                    "scraped_at": datetime.now().isoformat(),
                    "position": index + 1,
                    "author": article.get("author", ""),
                    "image": article.get("image", ""),
                    "api_category": article.get("category", "")
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
    logger.info(f"Tìm kiếm bài viết cho danh mục: {category} từ Currents API")
    
    try:
        # Tìm kiếm bài viết
        articles = search_news(category)
        
        if articles:
            logger.info(f"Tìm thấy {len(articles)} bài viết cho danh mục '{category}' từ Currents API")
            return articles
        else:
            logger.warning(f"Không tìm thấy bài viết nào cho danh mục '{category}' từ Currents API")
            return []
    
    except Exception as e:
        logger.error(f"Lỗi khi tìm kiếm bài viết từ Currents API cho danh mục {category}: {str(e)}")
        return []


def main():
    """
    Hàm chính để kiểm tra chức năng
    """
    if not API_KEY:
        print("Vui lòng thiết lập API key cho Currents API qua biến môi trường CURRENTS_API_KEY")
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
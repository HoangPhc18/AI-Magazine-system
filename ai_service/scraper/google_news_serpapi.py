#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Module để thu thập bài viết từ Google News
Chịu trách nhiệm tìm kiếm các bài viết theo danh mục.
"""

import os
import json
import time
import logging
import sys
import re
import random
import unicodedata
from datetime import datetime
from urllib.parse import urljoin, urlparse, parse_qs, quote_plus
import requests
from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, WebDriverException
import trafilatura
from dotenv import load_dotenv

# Tải biến môi trường từ file .env
load_dotenv()

# Cấu hình logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(f"scraper_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger()

# Các nguồn tin tức tin cậy ở Việt Nam
NEWS_SOURCES = [
    "vnexpress.net",
    "tuoitre.vn",
    "dantri.com.vn",
    "thanhnien.vn",
    "vietnamnet.vn",
    "nhandan.vn",
    "tienphong.vn",
    "zingnews.vn",
    "baomoi.com",
    "24h.com.vn"
]

# Danh sách User-Agents để luân phiên
USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36 Edg/113.0.1774.42",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/113.0",
    "Mozilla/5.0 (iPhone; CPU iPhone OS 16_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Mobile/15E148 Safari/604.1"
]

# 🔹 Số bài viết tối đa cho mỗi danh mục
MAX_ARTICLES_PER_CATEGORY = 1

# 🔹 Laravel Backend API URLs
BACKEND_API_URL = "http://localhost:8000/api/articles/import"
CATEGORIES_API_URL = "http://localhost:8000/api/categories"

# List of common Vietnamese news domains and their article content selectors
DOMAIN_SELECTORS = {
    "vnexpress.net": {
        "title": "h1.title-detail",
        "content": "article.fck_detail",
    },
    "tuoitre.vn": {
        "title": "h1.article-title",
        "content": "div.article-content",
    },
    "thanhnien.vn": {
        "title": "h1.details__headline",
        "content": "div.details__content",
    },
    "dantri.com.vn": {
        "title": "h1.title-page",
        "content": "div.dt-news__content",
    },
    "vietnamnet.vn": {
        "title": "h1.content-detail-title",
        "content": "div.content-detail-body",
    },
    "nhandan.vn": {
        "title": "h1.article__title",
        "content": "div.article__body",
    }
}

def remove_vietnamese_accents(text):
    """
    Loại bỏ dấu trong tiếng Việt.
    
    Args:
        text (str): Văn bản tiếng Việt có dấu
        
    Returns:
        str: Văn bản không dấu
    """
    # Bảng chuyển đổi chữ có dấu sang không dấu
    vietnamese_map = {
        'à': 'a', 'á': 'a', 'ả': 'a', 'ã': 'a', 'ạ': 'a',
        'ă': 'a', 'ằ': 'a', 'ắ': 'a', 'ẳ': 'a', 'ẵ': 'a', 'ặ': 'a',
        'â': 'a', 'ầ': 'a', 'ấ': 'a', 'ẩ': 'a', 'ẫ': 'a', 'ậ': 'a',
        'đ': 'd',
        'è': 'e', 'é': 'e', 'ẻ': 'e', 'ẽ': 'e', 'ẹ': 'e',
        'ê': 'e', 'ề': 'e', 'ế': 'e', 'ể': 'e', 'ễ': 'e', 'ệ': 'e',
        'ì': 'i', 'í': 'i', 'ỉ': 'i', 'ĩ': 'i', 'ị': 'i',
        'ò': 'o', 'ó': 'o', 'ỏ': 'o', 'õ': 'o', 'ọ': 'o',
        'ô': 'o', 'ồ': 'o', 'ố': 'o', 'ổ': 'o', 'ỗ': 'o', 'ộ': 'o',
        'ơ': 'o', 'ờ': 'o', 'ớ': 'o', 'ở': 'o', 'ỡ': 'o', 'ợ': 'o',
        'ù': 'u', 'ú': 'u', 'ủ': 'u', 'ũ': 'u', 'ụ': 'u',
        'ư': 'u', 'ừ': 'u', 'ứ': 'u', 'ử': 'u', 'ữ': 'u', 'ự': 'u',
        'ỳ': 'y', 'ý': 'y', 'ỷ': 'y', 'ỹ': 'y', 'ỵ': 'y',
    }
    
    # Phương pháp 1: Sử dụng bảng chuyển đổi
    result1 = ''.join(vietnamese_map.get(c.lower(), c) for c in text)
    
    # Phương pháp 2: Sử dụng unicodedata
    result2 = unicodedata.normalize('NFKD', text)
    result2 = ''.join([c for c in result2 if not unicodedata.combining(c)])
    result2 = result2.replace('đ', 'd').replace('Đ', 'D')
    
    # Sử dụng phương pháp 1 vì nó xử lý tốt hơn với chữ 'đ'
    return result1

def get_categories():
    """
    Lấy danh sách danh mục từ backend Laravel
    
    Returns:
        list: Danh sách tên danh mục
    """
    try:
        response = requests.get(CATEGORIES_API_URL)
        
        if response.status_code == 200:
            categories = response.json()
            category_names = [category["name"] for category in categories]
            print(f"[OK] Đã tải {len(category_names)} danh mục từ backend")
            print(f"[INFO] Danh sách danh mục:\n  - {', '.join(category_names)}")
            return category_names
        else:
            print(f"[ERROR] Lỗi khi lấy danh mục từ backend: {response.status_code} - {response.text}")
            # Sử dụng danh mục mặc định nếu không thể lấy từ backend
            default_categories = ["Công nghệ", "Kinh doanh", "Giải trí", "Thể thao", "Đời sống"]
            print(f"[INFO] Sử dụng danh mục mặc định: {', '.join(default_categories)}")
            return default_categories
    
    except Exception as e:
        print(f"[ERROR] Lỗi kết nối đến backend: {str(e)}")
        # Sử dụng danh mục mặc định nếu không thể kết nối
        default_categories = ["Công nghệ", "Kinh doanh", "Giải trí", "Thể thao", "Đời sống"]
        print(f"[INFO] Sử dụng danh mục mặc định: {', '.join(default_categories)}")
        return default_categories

def filter_articles(articles):
    """
    Lọc các bài viết theo tiêu chí, loại bỏ các bài không phù hợp
    
    Args:
        articles (list): Danh sách bài viết
        
    Returns:
        list: Danh sách bài viết đã lọc
    """
    filtered_articles = []
    
    for article in articles:
        url = article.get("source_url", "")
        
        # Loại bỏ URL video từ VTV.vn
        if "vtv.vn/video/" in url:
            logger.info(f"Bỏ qua bài viết video: {article.get('title', 'Không tiêu đề')} - {url}")
            continue
            
        # Kiểm tra các định dạng URL đa phương tiện khác
        media_patterns = ["/video/", "/clip/", "/podcast/", "/photo/", "/gallery/", "/infographic/"]
        skip_article = False
        
        for pattern in media_patterns:
            if pattern in url.lower():
                logger.warning(f"Bỏ qua URL đa phương tiện không phù hợp: {url}")
                skip_article = True
                break
        
        if skip_article:
            continue
        
        # Nếu qua được các tiêu chí lọc, thêm vào danh sách kết quả
        filtered_articles.append(article)
    
    return filtered_articles

def get_random_article_for_category(category):
    """
    Lấy một bài viết ngẫu nhiên từ nguồn tin cậy cho danh mục
    
    Args:
        category (str): Tên danh mục
        
    Returns:
        dict: Thông tin bài viết
    """
    # Chọn nguồn tin ngẫu nhiên
    source = random.choice(NEWS_SOURCES)
    
    # Mô phỏng tìm kiếm bài viết
    if source == "vnexpress.net":
        if category == "Công nghệ":
            return {
                "title": "Bản tin công nghệ mới nhất",
                "summary": "Tổng hợp tin tức công nghệ đáng chú ý",
                "content": None,
                "source_name": {
                    "name": "VnExpress",
                    "icon": "https://s.vnecdn.net/vnexpress/i/v1/logos/vne_logo_rss.png"
                },
                "source_url": f"https://vnexpress.net/khoa-hoc/cong-nghe",
                "source_icon": "https://s.vnecdn.net/vnexpress/i/v1/logos/vne_logo_rss.png",
                "published_at": datetime.now().strftime("%Y-%m-%dT%H:%M:%SZ"),
                "category": category,
                "meta_data": {
                    "original_source": "direct",
                    "scraped_at": datetime.now().isoformat(),
                    "position": 1
                }
            }
        elif category == "Kinh doanh":
            return {
                "title": "Diễn biến thị trường chứng khoán mới nhất",
                "summary": "Cập nhật thông tin thị trường tài chính",
                "content": None,
                "source_name": {
                    "name": "VnExpress",
                    "icon": "https://s.vnecdn.net/vnexpress/i/v1/logos/vne_logo_rss.png"
                },
                "source_url": f"https://vnexpress.net/kinh-doanh",
                "source_icon": "https://s.vnecdn.net/vnexpress/i/v1/logos/vne_logo_rss.png",
                "published_at": datetime.now().strftime("%Y-%m-%dT%H:%M:%SZ"),
                "category": category,
                "meta_data": {
                    "original_source": "direct",
                    "scraped_at": datetime.now().isoformat(),
                    "position": 1
                }
            }
        elif category == "Giải trí":
            return {
                "title": "Sự kiện giải trí nổi bật trong ngày",
                "summary": "Thông tin mới nhất về các ngôi sao và sự kiện giải trí",
                "content": None,
                "source_name": {
                    "name": "VnExpress",
                    "icon": "https://s.vnecdn.net/vnexpress/i/v1/logos/vne_logo_rss.png"
                },
                "source_url": f"https://vnexpress.net/giai-tri",
                "source_icon": "https://s.vnecdn.net/vnexpress/i/v1/logos/vne_logo_rss.png",
                "published_at": datetime.now().strftime("%Y-%m-%dT%H:%M:%SZ"),
                "category": category,
                "meta_data": {
                    "original_source": "direct",
                    "scraped_at": datetime.now().isoformat(),
                    "position": 1
                }
            }
        elif category == "Thể thao":
            return {
                "title": "Kết quả các trận đấu bóng đá mới nhất",
                "summary": "Cập nhật tin tức thể thao trong và ngoài nước",
                "content": None,
                "source_name": {
                    "name": "VnExpress",
                    "icon": "https://s.vnecdn.net/vnexpress/i/v1/logos/vne_logo_rss.png"
                },
                "source_url": f"https://vnexpress.net/the-thao",
                "source_icon": "https://s.vnecdn.net/vnexpress/i/v1/logos/vne_logo_rss.png",
                "published_at": datetime.now().strftime("%Y-%m-%dT%H:%M:%SZ"),
                "category": category,
                "meta_data": {
                    "original_source": "direct",
                    "scraped_at": datetime.now().isoformat(),
                    "position": 1
                }
            }
        else:  # Đời sống
            return {
                "title": "Những câu chuyện đời thường đáng chú ý",
                "summary": "Góc nhìn về cuộc sống và những câu chuyện đời thường",
                "content": None,
                "source_name": {
                    "name": "VnExpress",
                    "icon": "https://s.vnecdn.net/vnexpress/i/v1/logos/vne_logo_rss.png"
                },
                "source_url": f"https://vnexpress.net/doi-song",
                "source_icon": "https://s.vnecdn.net/vnexpress/i/v1/logos/vne_logo_rss.png",
                "published_at": datetime.now().strftime("%Y-%m-%dT%H:%M:%SZ"),
                "category": category,
                "meta_data": {
                    "original_source": "direct",
                    "scraped_at": datetime.now().isoformat(),
                    "position": 1
                }
            }
    elif source == "nhandan.vn":
        return {
            "title": f"Tin tức mới nhất về {category.lower()}",
            "summary": f"Cập nhật thông tin {category.lower()} hôm nay",
            "content": None,
            "source_name": {
                "name": "Nhân Dân",
                "icon": "https://img.nhandan.com.vn/Files/Images/2020/10/27/logo_d-1603758303260.png"
            },
            "source_url": f"https://nhandan.vn/{category.lower().replace(' ', '-')}",
            "source_icon": "https://img.nhandan.com.vn/Files/Images/2020/10/27/logo_d-1603758303260.png",
            "published_at": datetime.now().strftime("%Y-%m-%dT%H:%M:%SZ"),
            "category": category,
            "meta_data": {
                "original_source": "direct",
                "scraped_at": datetime.now().isoformat(),
                "position": 1
            }
        }
    else:
        # Tạo bài viết mặc định
        return {
            "title": f"Tin tức {category} cập nhật mới nhất",
            "summary": f"Tổng hợp tin tức {category} đáng chú ý trong ngày",
            "content": None,
            "source_name": {
                "name": source.split('.')[0].capitalize(),
                "icon": f"https://{source}/favicon.ico"
            },
            "source_url": f"https://{source}",
            "source_icon": f"https://{source}/favicon.ico",
            "published_at": datetime.now().strftime("%Y-%m-%dT%H:%M:%SZ"),
            "category": category,
            "meta_data": {
                "original_source": "direct",
                "scraped_at": datetime.now().isoformat(),
                "position": 1
            }
        }

def search_google_news(keyword):
    """
    Tìm kiếm từ khóa trên Google News và trả về URL bài viết đầu tiên.
    Thử cả từ khóa gốc và từ khóa không dấu.
    
    Args:
        keyword (str): Từ khóa tìm kiếm
        
    Returns:
        str: URL của bài viết đầu tiên, hoặc None nếu không tìm thấy
    """
    # Thử cả từ khóa gốc và từ khóa không dấu
    original_keyword = keyword
    keyword_no_accent = remove_vietnamese_accents(keyword)
    
    # Log thông tin từ khóa
    logger.info(f"Searching for keyword: '{original_keyword}' and non-accented version: '{keyword_no_accent}'")
    
    # Danh sách từ khóa để thử
    keywords_to_try = [original_keyword]
    
    # Nếu từ khóa không dấu khác với từ khóa gốc, thêm vào danh sách
    if keyword_no_accent != original_keyword:
        keywords_to_try.append(keyword_no_accent)
    
    # Lưu lỗi cho từng từ khóa
    errors = {}
    
    # Thử từng từ khóa
    for current_keyword in keywords_to_try:
        logger.info(f"Trying to search with keyword: '{current_keyword}'")
        
        try:
            # Tìm kiếm với Selenium
            logger.info(f"Attempting to search with Selenium for: '{current_keyword}'")
            url = search_with_selenium(current_keyword)
            
            if url:
                logger.info(f"Successfully found URL with Selenium: {url}")
                return url
            
            # Nếu Selenium thất bại, thử với HTTP request
            logger.info(f"Selenium failed, trying HTTP request method for: '{current_keyword}'")
            url = search_with_requests(current_keyword)
            
            if url:
                logger.info(f"Successfully found URL with HTTP request: {url}")
                return url
            
            # Nếu không tìm thấy, ghi lại lỗi
            error_msg = f"No articles found for keyword '{current_keyword}' using both methods"
            logger.error(error_msg)
            errors[current_keyword] = error_msg
            
        except Exception as e:
            error_msg = f"Exception searching for '{current_keyword}': {str(e)}"
            logger.error(error_msg)
            errors[current_keyword] = error_msg
    
    # Nếu tất cả các phương pháp thông thường thất bại, thử tìm kiếm trực tiếp
    logger.info("All standard methods failed, trying direct news search")
    url = direct_news_search(original_keyword)
    if url:
        logger.info(f"Found article through direct news search: {url}")
        return url
    
    # Tất cả các phương pháp đều thất bại
    logger.error(f"All keyword searches failed. Errors: {errors}")
    return None

def extract_article_content(url):
    """
    Extract article title and content from a URL.
    
    Args:
        url (str): URL of the article
        
    Returns:
        dict: Dictionary containing title and content
    """
    logger.info(f"Extracting content from: {url}")
    
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
        "Accept-Language": "vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
    }
    
    try:
        # Get the domain from the URL
        domain = urlparse(url).netloc
        logger.info(f"Detected domain: {domain}")
        
        # Fetch the page
        response = requests.get(url, headers=headers, timeout=15)
        response.raise_for_status()
        
        # Try using domain-specific selectors first
        for domain_key, selectors in DOMAIN_SELECTORS.items():
            if domain_key in domain:
                logger.info(f"Using custom selectors for domain: {domain_key}")
                soup = BeautifulSoup(response.text, 'html.parser')
                
                title_element = soup.select_one(selectors["title"])
                content_element = soup.select_one(selectors["content"])
                
                if title_element and content_element:
                    title = title_element.get_text().strip()
                    content = content_element.get_text().strip()
                    
                    # Clean up the content
                    content = re.sub(r'\s+', ' ', content)
                    
                    logger.info(f"Successfully extracted content using custom selectors (Title length: {len(title)}, Content length: {len(content)})")
                    return {
                        "title": title,
                        "content": content
                    }
        
        # If domain-specific extraction fails, fallback to trafilatura
        logger.info("Using trafilatura for content extraction")
        downloaded = trafilatura.fetch_url(url)
        
        if downloaded:
            # Extract with trafilatura
            result = trafilatura.extract(downloaded, output_format="json", include_comments=False, 
                                          include_tables=False, no_fallback=False)
            
            if result:
                import json
                data = json.loads(result)
                title = data.get("title", "")
                content = data.get("text", "")
                
                logger.info(f"Successfully extracted content using trafilatura (Title length: {len(title)}, Content length: {len(content)})")
                return {
                    "title": title,
                    "content": content
                }
        
        # If both methods fail, try a simple extraction with BeautifulSoup
        logger.info("Fallback to simple extraction with BeautifulSoup")
        soup = BeautifulSoup(response.text, 'html.parser')
        
        # Get title
        title = soup.title.string if soup.title else ""
        
        # Get main content (simple heuristic)
        main_content = ""
        paragraphs = soup.find_all('p')
        for p in paragraphs:
            text = p.get_text().strip()
            if len(text) > 50:  # Only consider paragraphs with substantial text
                main_content += text + "\n\n"
        
        if title and main_content:
            logger.info(f"Extracted content with simple fallback (Title length: {len(title)}, Content length: {len(main_content)})")
            return {
                "title": title,
                "content": main_content
            }
        
        logger.warning("Failed to extract content using all methods")
        return {
            "title": "",
            "content": ""
        }
        
    except requests.exceptions.RequestException as e:
        logger.error(f"Error fetching URL: {str(e)}")
        return {
            "title": "",
            "content": f"Error fetching URL: {str(e)}"
        }
    except Exception as e:
        logger.error(f"Unexpected error: {str(e)}")
        return {
            "title": "",
            "content": f"Error extracting content: {str(e)}"
        }

def fetch_articles_by_category(category_name):
    """
    Tìm kiếm bài viết cho một danh mục cụ thể
    
    Args:
        category_name (str): Tên danh mục cần tìm kiếm
        
    Returns:
        list: Danh sách bài viết với URL (không có nội dung)
    """
    articles = []
    
    try:
        # Print searching message
        logger.info(f"Tìm kiếm bài viết cho danh mục: {category_name}")
        
        # Tìm kiếm URL bài viết cho danh mục
        article_url = search_google_news(category_name)
        
        if article_url:
            logger.info(f"Tìm thấy bài viết cho danh mục {category_name}: {article_url}")
            
            # Tạo thông tin bài viết
            domain = urlparse(article_url).netloc
            source_name = domain.replace("www.", "").split(".")[0].capitalize()
            
            # Tạo bài viết với URL thực, nội dung sẽ được trích xuất sau bởi scrape_articles_selenium.py
            article = {
                "title": f"Tin tức mới nhất về {category_name}",  # Tiêu đề tạm thời
                "summary": f"Bài viết liên quan đến {category_name}",
                "content": None,  # Nội dung sẽ được trích xuất sau
                "source_name": source_name,
                "source_url": article_url,
                "source_icon": f"https://{domain}/favicon.ico",
                "published_at": datetime.now().strftime("%Y-%m-%dT%H:%M:%SZ"),
                "category": category_name,
                "meta_data": {
                    "original_source": "search",
                    "scraped_at": datetime.now().isoformat(),
                    "position": 1
                }
            }
            
            articles.append(article)
        else:
            logger.warning(f"Không tìm thấy bài viết nào cho danh mục '{category_name}' qua tìm kiếm.")
            
            # Sử dụng phương pháp cũ nếu tìm kiếm thất bại
            logger.info(f"Sử dụng bài viết mẫu cho danh mục: {category_name}")
        article = get_random_article_for_category(category_name)
        articles.append(article)
    
    except Exception as e:
        logger.error(f"Lỗi khi tìm kiếm bài viết cho danh mục {category_name}: {str(e)}")
        
        # Sử dụng phương pháp cũ nếu có lỗi
        logger.info(f"Sử dụng bài viết mẫu cho danh mục: {category_name} do lỗi")
        article = get_random_article_for_category(category_name)
        articles.append(article)
    
    # Apply filters to articles
    filtered_articles = filter_articles(articles)
    
    # Print success message
    if filtered_articles:
        logger.info(f"Tìm thấy {len(filtered_articles)} bài viết cho danh mục {category_name}")
    else:
        logger.warning(f"Không tìm thấy bài viết nào cho danh mục {category_name}")
    
    return filtered_articles

def send_to_backend(articles, auto_send=False):
    """
    Gửi bài viết tới backend API
    
    Args:
        articles (list): Danh sách bài viết
        auto_send (bool): Tự động gửi mà không cần xác nhận
        
    Returns:
        bool: Trạng thái thành công
    """
    if not articles:
        print("Không có bài viết nào để gửi!")
        return False
    
    send_option = "y" if auto_send else input("Bạn có muốn gửi bài viết tới backend? (y/n): ").lower()

    if send_option != "y":
        print("Đã hủy gửi dữ liệu tới backend")
        return False

    try:
        payload = {"articles": articles}
        headers = {"Content-Type": "application/json"}

        response = requests.post(BACKEND_API_URL, json=payload, headers=headers)

        if response.status_code in (200, 201):
            result = response.json()
            print(f"[OK] Đã gửi thành công {result.get('message', '')}")
            if 'errors' in result and result['errors']:
                print(f"[WARN] Có {len(result['errors'])} lỗi trong quá trình import:")
                for error in result['errors']:
                    print(f"  - {error}")
            return True
        else:
            print(f"[ERROR] Lỗi khi gửi bài viết tới backend: {response.status_code} - {response.text}")
            return False
    except Exception as e:
        print(f"[ERROR] Lỗi kết nối tới backend: {str(e)}")
        return False

def main():
    """
    Hàm chính để tìm kiếm và lưu các bài viết (chỉ URL, chưa có nội dung)
    """
    try:
        # Tạo thư mục output nếu chưa tồn tại
        output_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "output")
        os.makedirs(output_dir, exist_ok=True)
        
        # Lấy danh sách danh mục từ backend
        categories = get_categories()
        logger.info(f"Đã lấy {len(categories)} danh mục: {', '.join(categories)}")
    
        # Tìm kiếm bài viết cho mỗi danh mục
        all_articles = []
    
        for category in categories:
            logger.info(f"\n=== Đang xử lý danh mục: {category} ===")
            articles = fetch_articles_by_category(category)
            
            if articles:
                all_articles.extend(articles)
                logger.info(f"Đã tìm thấy {len(articles)} bài viết cho danh mục '{category}'")
            else:
                logger.warning(f"Không tìm thấy bài viết nào cho danh mục '{category}'")
    
        # Lưu bài viết vào file JSON trong thư mục output
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        output_file = os.path.join(output_dir, f"scraped_articles_{timestamp}.json")
    
        with open(output_file, "w", encoding="utf-8") as f:
            json.dump(all_articles, f, ensure_ascii=False, indent=4)
    
        logger.info(f"Tổng cộng {len(all_articles)} bài viết đã được lưu vào {output_file}")
        
        # Kiểm tra môi trường để quyết định có cần hỏi người dùng hay không
        auto_mode = os.environ.get('AUTO_SEND') == 'true' or '--auto-send' in sys.argv
        
        if auto_mode:
            # Tự động chuyển sang trích xuất nội dung và gửi đến backend
            logger.info("Chế độ tự động: tiếp tục trích xuất nội dung và gửi đến backend")
            try:
                import subprocess
                command = f"python scrape_articles_selenium.py {output_file} --auto-send"
                logger.info(f"Đang chạy lệnh: {command}")
                subprocess.run(command, shell=True)
            except Exception as e:
                logger.error(f"Lỗi khi chạy script trích xuất nội dung: {str(e)}")
                logger.error(traceback.format_exc())
                print(f"❌ Lỗi khi chạy script trích xuất nội dung. Bạn có thể chạy thủ công lệnh: python scrape_articles_selenium.py {output_file} --auto-send")
        else:
            # Hỏi người dùng khi chạy thủ công
            logger.info(f"Chạy scrape_articles_selenium.py để trích xuất nội dung đầy đủ từ các URL.")
            continue_to_scrape = input("Bạn có muốn tiếp tục trích xuất nội dung với scrape_articles_selenium.py? (y/n): ").lower()
            
            if continue_to_scrape == 'y':
                try:
                    import subprocess
                    # Hỏi người dùng có muốn tự động gửi đến backend không
                    auto_send = input("Bạn có muốn tự động gửi dữ liệu đến backend? (y/n): ").lower()
                    
                    if auto_send == 'y':
                        command = f"python scrape_articles_selenium.py {output_file} --auto-send"
                    else:
                        command = f"python scrape_articles_selenium.py {output_file}"
                        
                    logger.info(f"Đang chạy lệnh: {command}")
                    subprocess.run(command, shell=True)
                except Exception as e:
                    logger.error(f"Lỗi khi chạy script trích xuất nội dung: {str(e)}")
                    logger.error(traceback.format_exc())
                    print(f"❌ Lỗi khi chạy script trích xuất nội dung. Bạn có thể chạy thủ công lệnh: python scrape_articles_selenium.py {output_file}")
            else:
                print(f"✅ Đã hoàn thành tìm kiếm URL bài viết. Chạy lệnh sau để trích xuất nội dung: python scrape_articles_selenium.py {output_file}")
    
    except Exception as e:
        logger.error(f"Lỗi trong quá trình chạy chương trình: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())


def setup_selenium_driver():
    """
    Khởi tạo trình điều khiển Selenium với các tùy chọn phù hợp.
    
    Returns:
        webdriver: Trình điều khiển Selenium đã được cấu hình
    """
    try:
        # Thiết lập các tùy chọn cho Chrome
        chrome_options = Options()
        chrome_options.add_argument("--headless")  # Chạy trong chế độ headless (không hiển thị giao diện)
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--window-size=1920,1080")
        
        # Thêm User-Agent ngẫu nhiên
        user_agent = random.choice(USER_AGENTS)
        chrome_options.add_argument(f"--user-agent={user_agent}")
        
        # Tránh phát hiện tự động
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option('useAutomationExtension', False)
        
        # Thiết lập ngôn ngữ
        chrome_options.add_argument("--lang=vi-VN")
        
        # Khởi tạo trình điều khiển
        logger.info("Initializing Chrome WebDriver...")
        driver = webdriver.Chrome(
            service=Service(ChromeDriverManager().install()),
            options=chrome_options
        )
        
        # Thiết lập các thuộc tính để tránh phát hiện
        driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
        
        return driver
    
    except Exception as e:
        logger.error(f"Error setting up Selenium driver: {str(e)}")
        raise

def search_with_selenium(keyword):
    """
    Tìm kiếm trên Google News bằng Selenium và trả về URL bài viết đầu tiên.
    
    Args:
        keyword (str): Từ khóa tìm kiếm
        
    Returns:
        str: URL của bài viết đầu tiên, hoặc None nếu không tìm thấy
    """
    driver = None
    try:
        # Khởi tạo trình điều khiển
        driver = setup_selenium_driver()
        
        # Truy cập trực tiếp đến URL kết quả tìm kiếm, không dùng thanh tìm kiếm
        search_query = quote_plus(f"{keyword} when:1d")
        search_url = f"https://news.google.com/search?q={search_query}&hl=vi&gl=VN&ceid=VN:vi"
        
        logger.info(f"Accessing Google News search URL: {search_url}")
        driver.get(search_url)
        
        # Chờ kết quả tìm kiếm xuất hiện
        logger.info("Waiting for search results...")
        WebDriverWait(driver, 15).until(
            EC.presence_of_element_located((By.TAG_NAME, "article"))
        )
        
        # Thêm thời gian chờ để trang tải hoàn toàn
        time.sleep(3)
        
        # Lấy HTML của trang kết quả
        page_source = driver.page_source
        soup = BeautifulSoup(page_source, 'html.parser')
        
        # Tìm các bài viết trong kết quả
        articles = soup.select('article')
        
        if articles:
            logger.info(f"Found {len(articles)} articles in Google News")
            
            # Tìm bài viết đầu tiên có liên kết
            for article in articles:
                # Tìm tiêu đề bài viết
                title_element = article.select_one('h3 a, h4 a')
                
                if title_element:
                    logger.info(f"Found article with title: {title_element.text.strip()}")
                    
                    # Lấy liên kết từ bài viết
                    link = article.select_one('a[href^="./articles/"]')
                    
                    if link:
                        relative_url = link['href']
                        
                        # Truy cập vào liên kết để lấy URL thực
                        if relative_url.startswith('./'):
                            absolute_news_url = f"https://news.google.com{relative_url[1:]}"
                            logger.info(f"Navigating to article link: {absolute_news_url}")
                            
                            # Mở liên kết trong cửa sổ mới
                            driver.execute_script(f"window.open('{absolute_news_url}', '_blank');")
                            
                            # Chuyển sang cửa sổ mới
                            driver.switch_to.window(driver.window_handles[1])
                            
                            # Chờ chuyển hướng
                            time.sleep(5)
                            
                            # Lấy URL cuối cùng sau khi chuyển hướng
                            final_url = driver.current_url
                            
                            # Kiểm tra URL có phải là từ Google News không
                            if 'news.google.com' not in final_url:
                                logger.info(f"Successfully obtained article URL: {final_url}")
                                return final_url
            
            logger.warning("Could not extract actual URL from any article")
            
        else:
            logger.warning(f"No articles found for keyword: {keyword}")
        
        return None
        
    except TimeoutException:
        logger.error("Timeout waiting for page elements")
        return None
    
    except WebDriverException as e:
        logger.error(f"Selenium WebDriver error: {str(e)}")
        return None
    
    except Exception as e:
        logger.error(f"Error in Selenium search: {str(e)}")
        return None
    
    finally:
        # Đóng trình duyệt
        if driver:
            try:
                driver.quit()
                logger.info("WebDriver closed successfully")
            except Exception as e:
                logger.error(f"Error closing WebDriver: {str(e)}")

def search_with_requests(keyword):
    """
    Tìm kiếm trên Google News bằng requests HTTP thông thường.
    Được sử dụng như một phương án dự phòng nếu Selenium thất bại.
    
    Args:
        keyword (str): Từ khóa tìm kiếm
        
    Returns:
        str: URL của bài viết đầu tiên, hoặc None nếu không tìm thấy
    """
    # Danh sách các trang tin tức để tìm kiếm trực tiếp
    news_sites = [
        {
            'name': 'VnExpress',
            'search_url': f'https://timkiem.vnexpress.net/?q={quote_plus(keyword)}',
            'selectors': ['.title-news a', '.item-news a', '.title_news a']
        },
        {
            'name': 'Tuoi Tre',
            'search_url': f'https://tuoitre.vn/tim-kiem.htm?keywords={quote_plus(keyword)}',
            'selectors': ['.name-news a', '.title-news a']
        },
        {
            'name': 'Thanh Nien',
            'search_url': f'https://thanhnien.vn/tim-kiem/?q={quote_plus(keyword)}',
            'selectors': ['.story__title a', '.feature-box__title a']
        }
    ]
    
    headers = {
        'User-Agent': random.choice(USER_AGENTS),
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
    }
    
    for site in news_sites:
        try:
            logger.info(f"Searching on {site['name']}: {site['search_url']}")
            response = requests.get(site['search_url'], headers=headers, timeout=15)
            
            if response.status_code == 200:
                soup = BeautifulSoup(response.text, 'html.parser')
                
                # Thử các selector
                for selector in site['selectors']:
                    links = soup.select(selector)
                    logger.info(f"Found {len(links)} links with selector '{selector}' on {site['name']}")
                    
                    if links:
                        for link in links[:3]:  # Chỉ lấy 3 kết quả đầu tiên
                            href = link.get('href')
                            if href and ('http' in href or href.startswith('/')):
                                # Chuyển đổi URL tương đối thành tuyệt đối nếu cần
                                if not href.startswith('http'):
                                    base_url = urlparse(site['search_url'])
                                    href = f"{base_url.scheme}://{base_url.netloc}{href if href.startswith('/') else '/' + href}"
                                
                                logger.info(f"Found article on {site['name']}: {href}")
                                return href
        except Exception as e:
            logger.error(f"Error searching on {site['name']}: {str(e)}")
    
    logger.warning("No articles found in direct news search")
    return None

def direct_news_search(keyword):
    """
    Tìm kiếm trực tiếp trên các trang tin tức Việt Nam phổ biến.
    Phương pháp này được sử dụng khi Google News hoặc Google Search không hoạt động.
    
    Args:
        keyword (str): Từ khóa tìm kiếm
        
    Returns:
        str: URL bài viết nếu tìm thấy, None nếu không
    """
    logger.info(f"Performing direct news search for: {keyword}")
    
    # Danh sách các trang tin tức Việt Nam phổ biến
    news_sites = [
        {
            'name': 'VnExpress',
            'search_url': f"https://vnexpress.net/tim-kiem?q={quote_plus(keyword)}",
            'selectors': ['.title-news a', '.item-news h3 a', '.title_news a']
        },
        {
            'name': 'Tuổi Trẻ',
            'search_url': f"https://tuoitre.vn/tim-kiem.htm?keywords={quote_plus(keyword)}",
            'selectors': ['.news-item', '.title-news a', 'h3.title-news a']
        },
        {
            'name': 'Thanh Niên',
            'search_url': f"https://thanhnien.vn/tim-kiem/?q={quote_plus(keyword)}",
            'selectors': ['.story', '.story__title a', '.highlights__item-title a']
        },
        {
            'name': 'Dân Trí',
            'search_url': f"https://dantri.com.vn/tim-kiem?q={quote_plus(keyword)}",
            'selectors': ['.article-title a', '.article-item a', '.news-item__title a']
        },
        {
            'name': 'Zing News',
            'search_url': f"https://zingnews.vn/tim-kiem.html?q={quote_plus(keyword)}",
            'selectors': ['.article-title a', '.article-item a', '.news-item__title a']
        },
        {
            'name': 'VietnamNet',
            'search_url': f"https://vietnamnet.vn/tim-kiem?q={quote_plus(keyword)}",
            'selectors': ['.box-subcate-style1 a', '.title-href', '.horizontalPost__main-title']
        },
        {
            'name': 'Người Đưa Tin',
            'search_url': f"https://www.nguoiduatin.vn/tim-kiem?q={quote_plus(keyword)}",
            'selectors': ['article h3 a', '.article-title a']
        }
    ]
    
    headers = {
        'User-Agent': random.choice(USER_AGENTS),
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
    }
    
    for site in news_sites:
        try:
            logger.info(f"Searching on {site['name']}: {site['search_url']}")
            response = requests.get(site['search_url'], headers=headers, timeout=15)
            
            if response.status_code == 200:
                soup = BeautifulSoup(response.text, 'html.parser')
                
                # Thử các selector
                for selector in site['selectors']:
                    links = soup.select(selector)
                    logger.info(f"Found {len(links)} links with selector '{selector}' on {site['name']}")
                    
                    if links:
                        for link in links[:3]:  # Chỉ lấy 3 kết quả đầu tiên
                            href = link.get('href')
                            if href and ('http' in href or href.startswith('/')):
                                # Chuyển đổi URL tương đối thành tuyệt đối nếu cần
                                if not href.startswith('http'):
                                    base_url = urlparse(site['search_url'])
                                    href = f"{base_url.scheme}://{base_url.netloc}{href if href.startswith('/') else '/' + href}"
                                
                                logger.info(f"Found article on {site['name']}: {href}")
                                return href
        except Exception as e:
            logger.error(f"Error searching on {site['name']}: {str(e)}")
    
    logger.warning("No articles found in direct news search")
    return None

if __name__ == "__main__":
    main()

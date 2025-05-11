#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Module for searching Google News for a specific keyword using Selenium.
"""

import os
import sys
import re
import time
import random
import logging
import requests
import unicodedata
import json
from urllib.parse import urljoin, urlparse, parse_qs, quote_plus
from bs4 import BeautifulSoup
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, WebDriverException
from unidecode import unidecode

# Import cấu hình từ module config
from config import get_config

# Import the content extractor from scrape_articles_selenium.py
from scrape_articles_selenium import extract_article_content

# Tải cấu hình
config = get_config()

# Lấy thông tin cấu hình API URLs
BACKEND_URL = config["BACKEND_URL"]
BACKEND_PORT = config["BACKEND_PORT"]
BASE_API_URL = config["BASE_API_URL"]
CATEGORIES_API_URL = config["CATEGORIES_API_URL"]
SUBCATEGORIES_API_URL = config["SUBCATEGORIES_API_URL"]
BACKEND_API_URL = config["ARTICLES_API_URL"]
ARTICLES_IMPORT_API_URL = config["ARTICLES_IMPORT_API_URL"]
ARTICLES_CHECK_API_URL = config["ARTICLES_CHECK_API_URL"]

# 🔹 Số bài viết tối đa cho mỗi danh mục
MAX_ARTICLES_PER_CATEGORY = config.get("MAX_ARTICLES_PER_CATEGORY", 3)
MAX_ARTICLES_PER_SUBCATEGORY = config.get("MAX_ARTICLES_PER_SUBCATEGORY", 2)
USE_SUBCATEGORIES = config.get("USE_SUBCATEGORIES", True)

# Thông tin cấu hình service
PORT = config["PORT_SCRAPER"]
HOST = config["HOST"]
DEBUG = config["DEBUG"]

# Thư mục đầu ra JSON
OUTPUT_DIR = "output"
os.makedirs(OUTPUT_DIR, exist_ok=True)

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(f"keyword_search_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger()

# List of User-Agents to rotate
USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36 Edg/113.0.1774.42",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/113.0",
    "Mozilla/5.0 (iPhone; CPU iPhone OS 16_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Mobile/15E148 Safari/604.1"
]

def get_api_headers(content_type=None):
    """
    Tạo headers chuẩn cho API requests với xử lý đặc biệt cho magazine.test trên Linux
    
    Args:
        content_type (str, optional): Loại nội dung, ví dụ 'application/json'
        
    Returns:
        dict: Headers đã được chuẩn hóa
    """
    # Tải lại cấu hình để có thông tin mới nhất
    current_config = get_config()
    
    # Tạo headers cơ bản
    headers = {
        'User-Agent': random.choice(USER_AGENTS),
        'Accept': 'application/json',
    }
    
    # Thêm Content-Type nếu được chỉ định
    if content_type:
        headers['Content-Type'] = content_type
    
    # Thêm Host header nếu cấu hình yêu cầu
    if current_config.get("USE_HOST_HEADER", False):
        headers['Host'] = 'magazine.test'
        logger.debug("Sử dụng Host header: magazine.test")
    
    return headers

def fetch_categories_from_backend():
    """
    Lấy danh sách các danh mục từ backend.
    
    Returns:
        list: Danh sách các danh mục hoặc None nếu có lỗi
    """
    try:
        # Tải lại cấu hình để có thông tin mới nhất
        config = get_config()
        categories_url = config["CATEGORIES_API_URL"]
        
        # Gọi API lấy danh sách danh mục
        logger.info(f"Fetching categories from backend: {categories_url}")
        
        # Sử dụng hàm get_api_headers
        headers = get_api_headers()
        
        response = requests.get(categories_url, headers=headers, timeout=15)
        
        if response.status_code == 200:
            categories = response.json()
            
            # Kiểm tra cấu trúc dữ liệu trả về
            if not isinstance(categories, list):
                # Nếu không phải list, kiểm tra xem có phải là object với data key không
                if isinstance(categories, dict) and 'data' in categories:
                    categories = categories['data']
                else:
                    logger.error(f"Unexpected categories data structure: {categories}")
                    return None
            
            # Kiểm tra và log thông tin về các danh mục
            valid_categories = []
            for category in categories:
                if not isinstance(category, dict):
                    logger.warning(f"Invalid category data type: {type(category)}")
                    continue
                    
                if 'id' not in category or 'name' not in category:
                    logger.warning(f"Category missing required fields: {category}")
                    continue
                    
                # Ghi log thông tin danh mục
                logger.info(f"Found category: ID: {category['id']}, Name: {category['name']}, " +
                           f"Slug: {category.get('slug', 'N/A')}")
                           
                valid_categories.append(category)
            
            logger.info(f"Successfully fetched {len(valid_categories)} categories from backend")
            return valid_categories
        else:
            logger.error(f"Failed to fetch categories from backend. Status code: {response.status_code}")
            return None
            
    except Exception as e:
        logger.error(f"Error fetching categories from backend: {str(e)}")
        return None

def fetch_subcategories_by_category(category_id):
    """
    Lấy danh sách các danh mục con cho một danh mục cụ thể từ backend.
    
    Args:
        category_id (int): ID của danh mục cha
        
    Returns:
        list: Danh sách các danh mục con
    """
    try:
        # Tạo URL API để lấy danh mục con
        api_url = f"{CATEGORIES_API_URL}/{category_id}/subcategories"
        
        # Log thông tin request
        logger.info(f"Fetching subcategories for category ID {category_id} from: {api_url}")
        
        # Gửi request đến backend API
        headers = get_api_headers()
        
        response = requests.get(api_url, headers=headers, timeout=30)
        
        # Kiểm tra response
        if response.status_code == 200:
            subcategories = response.json()
            
            if isinstance(subcategories, list) and len(subcategories) > 0:
                logger.info(f"Found {len(subcategories)} subcategories for category ID {category_id}")
                for subcategory in subcategories[:3]:  # Hiển thị 3 danh mục con đầu tiên
                    logger.info(f"Subcategory: ID: {subcategory.get('id')}, Name: {subcategory.get('name')}")
                return subcategories
            else:
                logger.info(f"No subcategories found for category ID {category_id}")
                return []
        else:
            logger.error(f"Failed to fetch subcategories. Status code: {response.status_code}")
            return []
            
    except Exception as e:
        logger.error(f"Error fetching subcategories: {str(e)}")
        return []

def get_category_by_id(category_id):
    """
    Lấy thông tin danh mục từ backend dựa trên ID
    
    Args:
        category_id: ID của danh mục cần lấy
        
    Returns:
        dict: Thông tin danh mục hoặc None nếu không tìm thấy
    """
    try:
        logger.info(f"Fetching category with ID {category_id} from backend")
        headers = get_api_headers()
        response = requests.get(f"{CATEGORIES_API_URL}/{category_id}", headers=headers, timeout=15)
        
        if response.status_code == 200:
            category = response.json()
            logger.info(f"Successfully fetched category: {category['name']}")
            return category
        else:
            logger.error(f"Failed to fetch category with ID {category_id}. Status code: {response.status_code}")
            logger.error(f"Response: {response.text}")
            return None
            
    except Exception as e:
        logger.error(f"Error while fetching category with ID {category_id}: {str(e)}")
        return None

def get_subcategory_by_id(subcategory_id):
    """
    Lấy thông tin danh mục con từ backend dựa trên ID
    
    Args:
        subcategory_id: ID của danh mục con cần lấy
        
    Returns:
        dict: Thông tin danh mục con hoặc None nếu không tìm thấy
    """
    try:
        logger.info(f"Fetching subcategory with ID {subcategory_id} from backend")
        headers = get_api_headers()
        response = requests.get(f"{SUBCATEGORIES_API_URL}/{subcategory_id}", headers=headers, timeout=15)
        
        if response.status_code == 200:
            subcategory = response.json()
            logger.info(f"Successfully fetched subcategory: {subcategory['name']}")
            return subcategory
        else:
            logger.error(f"Failed to fetch subcategory with ID {subcategory_id}. Status code: {response.status_code}")
            logger.error(f"Response: {response.text}")
            return None
            
    except Exception as e:
        logger.error(f"Error while fetching subcategory with ID {subcategory_id}: {str(e)}")
        return None

def import_article_to_backend(category_id, article_url, title, content, subcategory_id=None):
    """
    Gửi bài viết đã tìm được vào backend.
    
    Args:
        category_id (int): ID của danh mục
        article_url (str): URL bài viết
        title (str): Tiêu đề bài viết
        content (str): Nội dung bài viết
        subcategory_id (int, optional): ID của danh mục con (nếu có)
        
    Returns:
        bool: True nếu thành công, False nếu thất bại
    """
    try:
        headers = get_api_headers('application/json')
        
        # Phân tích URL để lấy domain
        source_name = ""
        try:
            parsed_url = urlparse(article_url)
            source_name = parsed_url.netloc
        except:
            source_name = "unknown-source"
        
        # Tạo slug từ tiêu đề
        slug = generate_slug(title, add_uuid=True)
        
        # Tạo summary từ content
        summary = ""
        if content:
            sentences = re.split(r'[.!?]+', content)
            if len(sentences) >= 2:
                summary = '. '.join(s.strip() for s in sentences[:2] if s.strip()) + '.'
            else:
                summary = sentences[0].strip() if sentences else ""
        
        # Đảm bảo category_id là số nguyên
        try:
            category_id = int(category_id)
        except (ValueError, TypeError):
            logger.error(f"Invalid category_id: {category_id}")
            return False
        
        # Tạo article object
        article = {
            'category_id': category_id,
            'url': article_url,
            'source_url': article_url,
            'source_name': source_name,
            'source_icon': f"https://www.google.com/s2/favicons?domain={source_name}",
            'title': title,
            'slug': slug,
            'summary': summary,
            'content': content,
            'published_at': datetime.now().isoformat(),
            'is_published': 1,
            'is_imported': 1,
            'category': category_id  # Thêm field category để đảm bảo tương thích
        }
        
        # Thêm subcategory_id vào request nếu có
        if subcategory_id:
            # Đảm bảo subcategory_id là số nguyên
            try:
                subcategory_id = int(subcategory_id)
                article['subcategory_id'] = subcategory_id
            except (ValueError, TypeError):
                logger.error(f"Invalid subcategory_id: {subcategory_id}")
                # Không return False ở đây, chỉ không thêm subcategory_id vào request
            
            # Thêm thông tin danh mục con
            subcategory = get_subcategory_by_id(subcategory_id)
            if subcategory and 'name' in subcategory:
                article['subcategory_name'] = subcategory['name']
        
        # Đóng gói article trong mảng "articles" như API yêu cầu
        data = {
            'articles': [article]
        }
        
        # Log chi tiết request để debug
        logger.info(f"Article request: title='{title}', category_id={category_id}, subcategory_id={subcategory_id if subcategory_id else 'None'}")
        
        # Sử dụng endpoint import thay vì API articles trực tiếp
        import_endpoint = f"{ARTICLES_IMPORT_API_URL}"
        logger.info(f"Importing article to backend: {import_endpoint}")
        
        # Gửi request với timeout dài hơn để xử lý bài viết lớn
        response = requests.post(import_endpoint, headers=headers, json=data, timeout=60)
        
        if response.status_code == 200 or response.status_code == 201:
            # Phân tích kết quả trả về
            result = response.json()
            if result.get('status') == 'success':
                logger.info(f"Successfully imported article for category ID {category_id}{' and subcategory ID ' + str(subcategory_id) if subcategory_id else ''}")
                return True
            elif result.get('status') == 'warning':
                # Trường hợp có cảnh báo nhưng không lỗi
                logger.warning(f"Warning when importing article: {result.get('message')}")
                if result.get('skipped', 0) > 0:
                    # Bị bỏ qua nhưng không phải lỗi
                    logger.warning(f"Article was skipped. Reason: {result.get('errors', ['Unknown reason'])[0]}")
                    # Vẫn trả về True vì đây không phải lỗi kỹ thuật
                    return True
                return True
            else:
                logger.error(f"Failed to import article. Status: {result.get('status')}, Message: {result.get('message')}")
                return False
        else:
            logger.error(f"Failed to import article. Status code: {response.status_code}, Response: {response.text}")
            return False
            
    except Exception as e:
        logger.error(f"Error importing article: {str(e)}")
        return False

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
        
        # Truy cập Google News
        logger.info(f"Accessing Google News homepage with Selenium")
        driver.get("https://news.google.com/?hl=vi&gl=VN&ceid=VN:vi")
        
        # Chờ trang tải xong
        WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        
        # Tìm kiếm ô tìm kiếm và nhập từ khóa
        logger.info(f"Looking for search box on Google News")
        search_box = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, "input[type='text']"))
        )
        
        # Nhập từ khóa với "when:1d" để giới hạn trong 1 ngày
        search_query = f"{keyword} when:1d"
        logger.info(f"Entering search query: '{search_query}'")
        
        search_box.clear()
        search_box.send_keys(search_query)
        search_box.send_keys(Keys.RETURN)
        
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
    try:
        # Chuẩn bị headers với User-Agent ngẫu nhiên
        headers = {
            'User-Agent': random.choice(USER_AGENTS),
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
        }
        
        # Tìm kiếm trên Google News
        search_query = f"{keyword} when:1d"
        search_url = f"https://news.google.com/search?q={quote_plus(search_query)}&hl=vi&gl=VN&ceid=VN:vi"
        
        logger.info(f"Searching with HTTP request: {search_url}")
        response = requests.get(search_url, headers=headers, timeout=15)
        
        if response.status_code == 200:
            soup = BeautifulSoup(response.text, 'html.parser')
            
            # Tìm các bài viết
            articles = soup.select('article')
            
            if articles:
                logger.info(f"Found {len(articles)} articles via HTTP request")
                
                # Gỡ lỗi: Hiển thị tiêu đề của các bài viết
                for idx, article in enumerate(articles[:5]):
                    title_elem = article.select_one('h3, h4')
                    title = title_elem.text.strip() if title_elem else "No title"
                    logger.info(f"Article {idx+1}: {title}")
                
                # Thử trích xuất URL trực tiếp từ Google Search
                logger.info("Trying direct Google Search as it's more reliable")
                google_search_url = f"https://www.google.com/search?q={quote_plus(keyword)}&tbm=nws"
                logger.info(f"Searching with Google Search: {google_search_url}")
                
                try:
                    search_headers = {
                        'User-Agent': random.choice(USER_AGENTS),
                        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language': 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
                    }
                    
                    search_response = requests.get(google_search_url, headers=search_headers, timeout=15)
                    
                    if search_response.status_code == 200:
                        search_soup = BeautifulSoup(search_response.text, 'html.parser')
                        
                        # Log HTML để gỡ lỗi
                        with open('google_search_response.html', 'w', encoding='utf-8') as f:
                            f.write(search_response.text)
                        logger.info("Saved Google Search response to google_search_response.html")
                        
                        # Tìm các kết quả từ Google Search - thử nhiều selector khác nhau
                        for selector in ['div.g a', 'a[href^="https://"]', '.WlydOe', '.DhN8Cf a']:
                            search_results = search_soup.select(selector)
                            logger.info(f"Found {len(search_results)} results with selector '{selector}'")
                            
                            for result in search_results:
                                href = result.get('href')
                                if href and href.startswith('http') and 'google.com' not in href:
                                    logger.info(f"Found article URL from Google Search: {href}")
                                    return href
                    
                    logger.warning("No results found from Google Search")
                except Exception as e:
                    logger.error(f"Error with Google Search: {str(e)}")
                
                # Thử truy cập trực tiếp một trang tin tức Việt Nam có bài về từ khóa này
                news_sites = [
                    f"https://vnexpress.net/tim-kiem?q={quote_plus(keyword)}",
                    f"https://tuoitre.vn/tim-kiem.htm?keywords={quote_plus(keyword)}",
                    f"https://thanhnien.vn/tim-kiem/?q={quote_plus(keyword)}",
                    f"https://dantri.com.vn/tim-kiem?q={quote_plus(keyword)}"
                ]
                
                for site_url in news_sites:
                    try:
                        logger.info(f"Trying direct news site search: {site_url}")
                        site_response = requests.get(site_url, headers=headers, timeout=15)
                        
                        if site_response.status_code == 200:
                            site_soup = BeautifulSoup(site_response.text, 'html.parser')
                            
                            # Tìm kiếm các liên kết bài viết - thử nhiều selector khác nhau cho từng trang
                            for article_selector in ['article a', '.title-news a', '.story', '.article-title a', '.title a']:
                                article_links = site_soup.select(article_selector)
                                
                                for link in article_links[:3]:  # Chỉ xem 3 kết quả đầu tiên
                                    href = link.get('href')
                                    if href:
                                        # Chuyển đổi URL tương đối thành tuyệt đối nếu cần
                                        if not href.startswith('http'):
                                            base_url = urlparse(site_url)
                                            href = f"{base_url.scheme}://{base_url.netloc}{href if href.startswith('/') else '/' + href}"
                                        
                                        logger.info(f"Found article from direct news site: {href}")
                                        return href
                    except Exception as e:
                        logger.error(f"Error with direct news site {site_url}: {str(e)}")
            
            logger.warning("No suitable articles found via HTTP request")
        else:
            logger.error(f"HTTP request failed with status code: {response.status_code}")
        
        # Nếu tất cả các phương pháp đều thất bại, thử tìm kiếm với Bing
        try:
            bing_url = f"https://www.bing.com/news/search?q={quote_plus(keyword)}&qft=sortbydate%3d"
            logger.info(f"Trying Bing News as last resort: {bing_url}")
            
            bing_headers = {
                'User-Agent': random.choice(USER_AGENTS),
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language': 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
            }
            
            bing_response = requests.get(bing_url, headers=bing_headers, timeout=15)
            
            if bing_response.status_code == 200:
                bing_soup = BeautifulSoup(bing_response.text, 'html.parser')
                
                # Tìm kết quả tin tức từ Bing
                bing_results = bing_soup.select('.news-card a')
                
                for result in bing_results:
                    href = result.get('href')
                    if href and href.startswith('http') and 'bing.com' not in href and 'msn.com' not in href:
                        logger.info(f"Found article from Bing News: {href}")
                        return href
        except Exception as e:
            logger.error(f"Error with Bing search: {str(e)}")
        
        return None
    
    except Exception as e:
        logger.error(f"Error in HTTP request method: {str(e)}")
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
            'name': 'Bóng Đá 24h',
            'search_url': f"https://bongda24h.vn/tim-kiem/{quote_plus(keyword)}/1.html",
            'selectors': ['.news-title a', '.title a', 'h3 a']
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
                
                # Lưu HTML để gỡ lỗi nếu cần
                with open(f"{site['name'].lower()}_search.html", 'w', encoding='utf-8') as f:
                    f.write(response.text)
                
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
            # Vô hiệu hóa Selenium vì lỗi WebDriver trên Windows
            # logger.info(f"Attempting to search with Selenium for: '{current_keyword}'")
            # url = search_with_selenium(current_keyword)
            # 
            # if url:
            #     logger.info(f"Successfully found URL with Selenium: {url}")
            #     return url
            
            # Chỉ sử dụng phương pháp requests HTTP
            logger.info(f"Using HTTP request method for: '{current_keyword}'")
            url = search_with_requests(current_keyword)
            
            if url:
                logger.info(f"Successfully found URL with HTTP request: {url}")
                return url
            
            # Nếu không tìm thấy, ghi lại lỗi
            error_msg = f"No articles found for keyword '{current_keyword}' using HTTP request method"
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

def search_with_category(category_id, subcategory_id=None):
    """
    Tìm kiếm bài viết dựa trên ID danh mục hoặc danh mục con từ backend.
    
    Args:
        category_id (int): ID của danh mục
        subcategory_id (int, optional): ID của danh mục con (nếu có)
        
    Returns:
        dict: Thông tin bài viết đã tìm thấy và trích xuất, hoặc None nếu thất bại
    """
    try:
        # Ưu tiên tìm kiếm với subcategory nếu có
        if subcategory_id:
            subcategory = get_subcategory_by_id(subcategory_id)
            
            if not subcategory:
                logger.error(f"Could not find subcategory with ID: {subcategory_id}")
                return None
            
            # Sử dụng tên danh mục con làm từ khóa tìm kiếm
            if 'name' not in subcategory:
                logger.error(f"Subcategory data does not contain 'name' field: {subcategory}")
                return None
                
            keyword = subcategory['name']
            logger.info(f"Using subcategory name '{keyword}' as search keyword")
            
            # Lấy thông tin danh mục chính
            category = get_category_by_id(category_id)
            
            if not category:
                logger.error(f"Could not find category with ID: {category_id}")
                return None
                
            category_name = category['name']
        else:
            # Lấy thông tin danh mục từ backend
            category = get_category_by_id(category_id)
            
            if not category:
                logger.error(f"Could not find category with ID: {category_id}")
                return None
            
            # Sử dụng tên danh mục (cột name) làm từ khóa tìm kiếm
            if 'name' not in category:
                logger.error(f"Category data does not contain 'name' field: {category}")
                return None
                
            keyword = category['name']
            logger.info(f"Using category name '{keyword}' as search keyword")
            category_name = keyword
        
        # Tìm kiếm bài viết với từ khóa
        article_url = search_google_news(keyword)
        
        if not article_url:
            logger.error(f"No article URL found for keyword: {keyword}")
            return None
        
        # Kiểm tra URL
        if not article_url.startswith('http'):
            logger.error(f"Invalid URL format: {article_url}")
            return None
            
        # Kiểm tra bài viết đã tồn tại trong database chưa
        if check_article_exists(article_url):
            logger.warning(f"Bài viết đã tồn tại trong database, tìm bài viết khác: {article_url}")
            # Thử tìm URL khác nếu URL này đã tồn tại
            for attempt in range(3):
                logger.info(f"Attempting to find a different article (attempt {attempt+1}/3)")
                new_url = search_google_news(keyword + f" -{article_url.split('/')[2]}")
                if new_url and new_url != article_url and not check_article_exists(new_url):
                    article_url = new_url
                    logger.info(f"Found alternative URL: {article_url}")
                    break
                time.sleep(1)
        
        logger.info(f"Found article URL: {article_url}, extracting content...")
        
        # Trích xuất nội dung bài viết
        article_data = extract_article_content(article_url)
        
        if not article_data:
            logger.error(f"Failed to extract any content from URL: {article_url}")
            return None
            
        # Kiểm tra dữ liệu có đủ các trường cần thiết không
        if not article_data.get("title"):
            logger.error(f"Extracted article has no title: {article_url}")
            return None
            
        if not article_data.get("content"):
            logger.error(f"Extracted article has no content: {article_url}")
            return None
        
        # Kiểm tra độ dài nội dung
        content_length = len(article_data.get("content", ""))
        if content_length < 100:
            logger.error(f"Article content too short ({content_length} chars): {article_url}")
            return None
            
        logger.info(f"Successfully extracted content from URL: {article_url}")
        logger.info(f"Title: {article_data.get('title')}")
        logger.info(f"Content length: {content_length} chars")
        
        # Lưu dữ liệu bài viết vào file JSON
        json_filepath = save_article_to_json(
            category_id=category_id,
            category_name=category_name,
            article_url=article_url,
            article_data=article_data,
            subcategory_id=subcategory_id,
            subcategory_name=keyword if subcategory_id else None
        )
        
        if not json_filepath:
            logger.error(f"Failed to save article to JSON for keyword: {keyword}")
            return None
        
        # Lưu thông tin vào backend nếu bài viết chưa tồn tại
        import_success = False
        if not check_article_exists(article_url):
            import_success = import_article_to_backend(
                category_id, 
                article_url, 
                article_data["title"], 
                article_data["content"],
                subcategory_id
            )
            if import_success:
                logger.info(f"Successfully imported article to backend")
            else:
                logger.warning(f"Failed to import article to backend, but continuing with local save")
        else:
            logger.info(f"Bỏ qua import vì bài viết đã tồn tại trong database")
            import_success = True  # Đánh dấu là thành công vì bài viết đã tồn tại
        
        result = {
            "category_id": category_id,
            "category_name": category_name,
            "url": article_url,
            "title": article_data.get("title", ""),
            "content_length": len(article_data.get("content", "")),
            "json_filepath": json_filepath,
            "import_success": import_success
        }
        
        if subcategory_id:
            result["subcategory_id"] = subcategory_id
            result["subcategory_name"] = keyword
        
        return result
        
    except Exception as e:
        logger.error(f"Error in search_with_category: {str(e)}")
        return None

def process_all_categories():
    """
    Xử lý tất cả các danh mục từ backend, chỉ tìm kiếm và lưu trữ bài viết cho các danh mục con.
    
    Returns:
        dict: Kết quả xử lý các danh mục
    """
    result = {
        'success': 0,
        'failed': 0,
        'categories': []
    }
    
    try:
        # Lấy danh sách danh mục từ backend
        categories = fetch_categories_from_backend()
        
        if not categories:
            logger.error("Failed to fetch categories from backend")
            return result
        
        logger.info(f"Processing {len(categories)} categories")
        
        # Xử lý từng danh mục
        for category in categories:
            # Kiểm tra cấu trúc danh mục hợp lệ
            category_id = category.get('id')
            category_name = category.get('name')
            
            if not category_id or not category_name:
                logger.warning(f"Invalid category data: {category}")
                result['failed'] += 1
                result['categories'].append({
                    'id': category_id,
                    'name': category_name,
                    'status': 'failed',
                    'error': 'Invalid category data - missing id or name field'
                })
                continue
                
            # Log thông tin chi tiết về danh mục
            logger.info(f"Processing category: ID: {category_id}, Name: {category_name}, Slug: {category.get('slug', 'N/A')}")
            
            # Lấy danh sách danh mục con
            subcategories = fetch_subcategories_by_category(category_id)
            
            if subcategories and len(subcategories) > 0:
                logger.info(f"Found {len(subcategories)} subcategories for category {category_name}")
                
                # Xử lý từng danh mục con
                for subcategory in subcategories:
                    subcategory_id = subcategory.get('id')
                    subcategory_name = subcategory.get('name')
                    
                    logger.info(f"Processing subcategory: ID: {subcategory_id}, Name: {subcategory_name}")
                    
                    # Giới hạn số bài viết theo cấu hình
                    articles_per_subcategory = MAX_ARTICLES_PER_SUBCATEGORY
                    
                    # Tìm kiếm và trích xuất nội dung bài viết cho danh mục con này
                    for i in range(articles_per_subcategory):
                        if result['success'] >= MAX_ARTICLES_PER_CATEGORY and MAX_ARTICLES_PER_CATEGORY > 0:
                            logger.info(f"Reached maximum articles limit ({MAX_ARTICLES_PER_CATEGORY}), stopping")
                            break
                            
                        logger.info(f"Finding article {i+1}/{articles_per_subcategory} for subcategory {subcategory_name}")
                        
                        # Tìm kiếm bài viết với subcategory
                        article_result = search_with_category(category_id, subcategory_id)
                        
                        if article_result:
                            # Đánh dấu thành công và lưu kết quả
                            result['success'] += 1
                            result['categories'].append({
                                'id': category_id,
                                'name': category_name,
                                'subcategory_id': subcategory_id,
                                'subcategory_name': subcategory_name,
                                'status': 'success',
                                'url': article_result['url'],
                                'title': article_result['title'],
                                'content_length': article_result['content_length'],
                                'json_filepath': article_result['json_filepath']
                            })
                        else:
                            logger.warning(f"Failed to find article for subcategory: {subcategory_name}")
                            break
                            
                        # Thêm thời gian nghỉ để tránh bị chặn
                        time.sleep(2)
            else:
                logger.info(f"No subcategories found for category {category_name}. Skipping.")
                # Không còn tìm kiếm cho category khi không có subcategory
                continue
        
        logger.info(f"Processed all categories. Success: {result['success']}, Failed: {result['failed']}")
        return result
            
    except Exception as e:
        logger.error(f"Error processing categories: {str(e)}")
        return result

def save_article_to_json(category_id, category_name, article_url, article_data, subcategory_id=None, subcategory_name=None):
    """
    Lưu thông tin bài viết vào file JSON.
    
    Args:
        category_id (int): ID của danh mục
        category_name (str): Tên danh mục
        article_url (str): URL của bài viết
        article_data (dict): Dữ liệu bài viết gồm title và content
        subcategory_id (int, optional): ID của danh mục con (nếu có)
        subcategory_name (str, optional): Tên danh mục con (nếu có)
        
    Returns:
        str: Đường dẫn đến file JSON đã lưu
    """
    try:
        # Kiểm tra và log chi tiết về dữ liệu đầu vào
        logger.info(f"Saving article to JSON: category_id={category_id}, subcategory_id={subcategory_id}")
        
        # Đảm bảo có title
        title = article_data.get("title", "").strip()
        if not title:
            logger.error("Cannot save article without a title")
            return None
        
        # Đảm bảo có content
        content = article_data.get("content", "").strip()
        if not content:
            logger.error("Cannot save article without content")
            return None
            
        # Đảm bảo category_id là số nguyên
        try:
            category_id = int(category_id)
        except (ValueError, TypeError):
            logger.error(f"Invalid category_id: {category_id}")
            return None
            
        # Đảm bảo subcategory_id là số nguyên nếu có
        if subcategory_id:
            try:
                subcategory_id = int(subcategory_id)
            except (ValueError, TypeError):
                logger.error(f"Invalid subcategory_id: {subcategory_id}")
                subcategory_id = None
        
        # Tạo tên file từ tiêu đề bài viết
        # Loại bỏ ký tự không hợp lệ cho tên file
        title_slug = generate_slug(title, add_uuid=True)
        
        # Tạo tên file với format: category_id-subcategory_id-title_slug.json (nếu có subcategory)
        # hoặc category_id-title_slug.json (nếu không có subcategory)
        if subcategory_id:
            filename = os.path.join(OUTPUT_DIR, f"{category_id}-{subcategory_id}-{title_slug}.json")
        else:
            filename = os.path.join(OUTPUT_DIR, f"{category_id}-{title_slug}.json")
        
        # Phân tích URL để lấy domain
        source_name = ""
        try:
            parsed_url = urlparse(article_url)
            source_name = parsed_url.netloc
        except:
            source_name = "unknown-source"

        # Xác định summary từ nội dung nếu không có
        content = article_data.get("content", "")
        summary = article_data.get("summary", "")
        if not summary and content:
            sentences = re.split(r'[.!?]+', content)
            if len(sentences) >= 2:
                summary = '. '.join(s.strip() for s in sentences[:2] if s.strip()) + '.'
            else:
                summary = sentences[0].strip() if sentences else ""
                
        # Đảm bảo summary không quá dài
        if len(summary) > 300:
            summary = summary[:297] + "..."
        
        # Chuẩn bị dữ liệu JSON
        article_json = {
            "category_id": category_id,
            "category_name": category_name,
            "category": category_id,  # Thêm trường này cho tương thích với API
            "url": article_url,
            "source_url": article_url,
            "source_name": source_name,
            "source_icon": f"https://www.google.com/s2/favicons?domain={source_name}",
            "title": title,
            "slug": title_slug,
            "summary": summary,
            "content": content,
            "published_at": datetime.now().isoformat(),
            "extracted_at": datetime.now().isoformat(),
            "is_published": 1,
            "is_imported": 1
        }
        
        # Thêm thông tin subcategory nếu có
        if subcategory_id:
            article_json["subcategory_id"] = subcategory_id
            article_json["subcategory_name"] = subcategory_name
        
        # Ghi file JSON
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(article_json, f, ensure_ascii=False, indent=4)
            
        logger.info(f"Saved article to file: {filename}")
        return filename
        
    except Exception as e:
        logger.error(f"Error saving article to JSON: {str(e)}")
        return None

def check_article_exists(url):
    """
    Kiểm tra một bài viết đã tồn tại trong database hay chưa dựa trên URL
    
    Args:
        url (str): URL của bài viết cần kiểm tra
    
    Returns:
        bool: True nếu bài viết đã tồn tại, False nếu chưa tồn tại hoặc có lỗi
    """
    try:
        # URL để kiểm tra bài viết
        check_url = f"{ARTICLES_CHECK_API_URL}"
        
        # Sử dụng hàm get_api_headers để lấy headers nhất quán
        headers = get_api_headers('application/json')
        
        data = {
            'url': url
        }
        
        logger.info(f"Kiểm tra bài viết đã tồn tại: {url}")
        logger.info(f"Gửi request đến: {check_url} với headers: {headers}")
        
        response = requests.post(check_url, headers=headers, json=data, timeout=10)
        
        # Log phản hồi
        logger.info(f"Response status code: {response.status_code}")
        
        if response.status_code == 200:
            result = response.json()
            exists = result.get('exists', False)
            
            if exists:
                logger.warning(f"Bài viết đã tồn tại trong database: {url}")
                return True
            else:
                logger.info(f"Bài viết chưa tồn tại trong database: {url}")
                return False
        else:
            # Nếu API thất bại, thử sử dụng domain trực tiếp nếu đang sử dụng Host header
            current_config = get_config()
            if current_config.get("USE_HOST_HEADER", False):
                try:
                    direct_url = "http://magazine.test/api/articles/check"
                    logger.info(f"Thử kiểm tra bài viết qua domain trực tiếp: {direct_url}")
                    direct_response = requests.post(direct_url, headers={'Content-Type': 'application/json'}, json=data, timeout=10)
                    
                    if direct_response.status_code == 200:
                        direct_result = direct_response.json()
                        direct_exists = direct_result.get('exists', False)
                        logger.info(f"Kết quả kiểm tra qua domain: {direct_exists}")
                        return direct_exists
                except Exception as domain_err:
                    logger.error(f"Không thể kiểm tra qua domain: {str(domain_err)}")
            
            # Nếu API thất bại, giả định bài viết chưa tồn tại để tiếp tục xử lý
            logger.warning(f"Không thể kiểm tra bài viết, giả định chưa tồn tại: {url}")
            return False
            
    except Exception as e:
        logger.error(f"Lỗi khi kiểm tra bài viết: {str(e)}")
        # Trong trường hợp lỗi, giả định bài viết chưa tồn tại để tiếp tục xử lý
        return False

def generate_slug(text, max_length=50, add_uuid=False):
    """
    Tạo slug từ chuỗi văn bản, phù hợp cho tên file và URL.
    
    Args:
        text (str): Chuỗi văn bản cần tạo slug
        max_length (int): Độ dài tối đa của slug
        add_uuid (bool): Thêm UUID để đảm bảo slug là duy nhất
        
    Returns:
        str: Slug được tạo từ chuỗi văn bản
    """
    try:
        if not text:
            logger.warning("Empty text provided for slug generation")
            text = "article"
            
        # Chuyển sang chữ thường và loại bỏ dấu tiếng Việt
        text = text.lower().strip()
        text = remove_vietnamese_accents(text)
        
        # Loại bỏ các ký tự không phải chữ cái, số, dấu cách
        text = re.sub(r'[^\w\s-]', '', text)
        
        # Thay thế dấu cách bằng dấu gạch ngang
        text = re.sub(r'\s+', '-', text)
        
        # Loại bỏ nhiều dấu gạch ngang liên tiếp
        text = re.sub(r'-+', '-', text)
        
        # Loại bỏ dấu gạch ngang ở đầu và cuối
        text = text.strip('-')
        
        # Giới hạn độ dài
        if len(text) > max_length:
            text = text[:max_length].rstrip('-')
        
        # Kiểm tra nếu slug quá ngắn
        if len(text) < 3:
            text = f"article-{text}"
        
        # Thêm UUID nếu cần
        if add_uuid:
            import uuid
            uuid_str = str(uuid.uuid4())[:8]
            text = f"{text}-{uuid_str}"
            
        return text
    except Exception as e:
        logger.error(f"Error generating slug: {str(e)}")
        # Trả về một giá trị mặc định nếu có lỗi
        import uuid
        return f"article-{str(uuid.uuid4())[:8]}"

if __name__ == '__main__':
    # Kiểm tra xem có chạy chế độ xử lý tất cả danh mục hay không
    if len(sys.argv) > 1 and sys.argv[1] == '--all-categories':
        logger.info("Processing all categories from backend")
        results = process_all_categories()
        print(f"Processed {len(results['categories'])} categories. Success: {results['success']}, Failed: {results['failed']}")
        
        # Hiển thị thêm thông tin về các bài viết đã xử lý thành công
        for category in results['categories']:
            if category['status'] == 'success':
                print(f"\nSuccessful category: {category['name']} (ID: {category['id']})")
                print(f"  URL: {category['url']}")
                print(f"  Title: {category['title']}")
                print(f"  Content length: {category['content_length']} characters")
                print(f"  Saved to: {category['json_filepath']}")
    elif len(sys.argv) > 1:
        # Kiểm tra xem tham số là category_id hay keyword
        param = sys.argv[1]
        
        # Nếu tham số là số, xem như category_id
        if param.isdigit():
            category_id = int(param)
            logger.info(f"Searching with category ID: {category_id}")
            result = search_with_category(category_id)
            
            if result:
                print(f"Successfully processed article for category ID: {category_id}")
                print(f"URL: {result['url']}")
                print(f"Title: {result['title']}")
                print(f"Content length: {result['content_length']} characters")
                print(f"Saved to: {result['json_filepath']}")
            else:
                print(f"No article found or failed to process for category ID: {category_id}")
        else:
            # Ngược lại, xem như keyword
            keyword = param
            logger.info(f"Searching with keyword: {keyword}")
            url = search_google_news(keyword)
            
            if url:
                print(f"Found URL: {url}")
                print("Extracting content...")
                
                # Trích xuất nội dung
                article_data = extract_article_content(url)
                
                if article_data and article_data.get("title") and article_data.get("content"):
                    print(f"Successfully extracted content:")
                    print(f"Title: {article_data['title']}")
                    print(f"Content length: {len(article_data['content'])} characters")
                    
                    # Lưu vào file JSON
                    json_filepath = save_article_to_json(
                        category_id=0,  # 0 vì không có category_id
                        category_name=keyword,
                        article_url=url,
                        article_data=article_data
                    )
                    
                    if json_filepath:
                        print(f"Saved to: {json_filepath}")
                else:
                    print(f"Failed to extract content from: {url}")
            else:
                print(f"No URL found for keyword: {keyword}")
    else:
        print("Usage: python google_news.py <keyword or category_id>")
        print("       python google_news.py --all-categories") 
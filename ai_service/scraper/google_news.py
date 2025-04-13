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

# Import the content extractor from scrape_articles_selenium.py
from scrape_articles_selenium import extract_article_content

# 🔹 Số bài viết tối đa cho mỗi danh mục
MAX_ARTICLES_PER_CATEGORY = 3

# Set environment variables for Flask
os.environ["PORT"] = "5001"
os.environ["HOST"] = "0.0.0.0"
os.environ["DEBUG"] = "False"

# API URLs
BACKEND_API_URL = "http://host.docker.internal:8000/api/articles/import"
CATEGORIES_API_URL = "http://host.docker.internal:8000/api/categories"

# Thư mục đầu ra JSON
OUTPUT_DIR = "output"

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

def fetch_categories_from_backend():
    """
    Lấy danh sách các danh mục từ backend.
    
    Returns:
        list: Danh sách các danh mục hoặc None nếu có lỗi
    """
    try:
        headers = {
            'User-Agent': random.choice(USER_AGENTS),
            'Accept': 'application/json',
        }
        
        # Gọi API lấy danh sách danh mục
        logger.info(f"Fetching categories from backend: {CATEGORIES_API_URL}")
        
        response = requests.get(CATEGORIES_API_URL, headers=headers, timeout=15)
        
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
        response = requests.get(f"{CATEGORIES_API_URL}/{category_id}")
        
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

def import_article_to_backend(category_id, article_url, title, content):
    """
    Gửi bài viết đã tìm được vào backend.
    
    Args:
        category_id (int): ID của danh mục
        article_url (str): URL bài viết
        title (str): Tiêu đề bài viết
        content (str): Nội dung bài viết
        
    Returns:
        bool: True nếu thành công, False nếu thất bại
    """
    try:
        headers = {
            'User-Agent': random.choice(USER_AGENTS),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
        
        data = {
            'category_id': category_id,
            'url': article_url,
            'title': title,
            'content': content
        }
        
        logger.info(f"Importing article to backend: {BACKEND_API_URL}")
        response = requests.post(BACKEND_API_URL, headers=headers, json=data, timeout=30)
        
        if response.status_code == 200 or response.status_code == 201:
            logger.info(f"Successfully imported article for category ID {category_id}")
            return True
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

def search_with_category(category_id):
    """
    Tìm kiếm bài viết dựa trên ID danh mục từ backend.
    
    Args:
        category_id (int): ID của danh mục
        
    Returns:
        dict: Thông tin bài viết đã tìm thấy và trích xuất, hoặc None nếu thất bại
    """
    try:
        # Lấy thông tin danh mục từ backend
        category = get_category_by_id(category_id)
        
        if not category:
            logger.error(f"Could not find category with ID: {category_id}")
            return None
        
        # Sử dụng tên danh mục (cột name) làm từ khóa tìm kiếm
        if 'name' not in category:
            logger.error(f"Category data does not contain 'name' field: {category}")
            return None
            
        category_name = category['name']
        logger.info(f"Using category name '{category_name}' as search keyword")
        
        # Tìm kiếm bài viết với từ khóa là tên danh mục
        article_url = search_google_news(category_name)
        
        if not article_url:
            logger.error(f"No article URL found for category: {category_name}")
            return None
            
        logger.info(f"Found article URL: {article_url}, extracting content...")
        
        # Trích xuất nội dung bài viết
        article_data = extract_article_content(article_url)
        
        if not article_data or not article_data.get("title") or not article_data.get("content"):
            logger.error(f"Failed to extract content from URL: {article_url}")
            return None
            
        logger.info(f"Successfully extracted content from URL: {article_url}")
        logger.info(f"Title: {article_data.get('title')}")
        logger.info(f"Content length: {len(article_data.get('content', ''))}")
        
        # Lưu dữ liệu bài viết vào file JSON
        json_filepath = save_article_to_json(
            category_id=category_id,
            category_name=category_name,
            article_url=article_url,
            article_data=article_data
        )
        
        if not json_filepath:
            logger.error(f"Failed to save article to JSON for category: {category_name}")
        
        # Lưu thông tin vào backend nếu cần
        # import_article_to_backend(category_id, article_url, article_data["title"], article_data["content"])
        
        return {
            "category_id": category_id,
            "category_name": category_name,
            "url": article_url,
            "title": article_data.get("title", ""),
            "content_length": len(article_data.get("content", "")),
            "json_filepath": json_filepath
        }
        
    except Exception as e:
        logger.error(f"Error in search_with_category: {str(e)}")
        return None

def process_all_categories():
    """
    Xử lý tất cả các danh mục từ backend, tìm kiếm và lưu trữ bài viết cho mỗi danh mục.
    
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
            # Kiểm tra cấu trúc danh mục hợp lệ (id, name, slug, description, created_at, updated_at, deleted_at)
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
            
            # Giới hạn số bài viết theo cấu hình
            if result['success'] >= MAX_ARTICLES_PER_CATEGORY and MAX_ARTICLES_PER_CATEGORY > 0:
                logger.info(f"Reached maximum number of articles per category: {MAX_ARTICLES_PER_CATEGORY}")
                break
            
            # Tìm kiếm và trích xuất nội dung bài viết cho danh mục này
            article_result = search_with_category(category_id)
            
            if not article_result:
                logger.error(f"No article found or failed to process for category: {category_name}")
                result['failed'] += 1
                result['categories'].append({
                    'id': category_id,
                    'name': category_name,
                    'status': 'failed',
                    'error': 'No article found or processing failed'
                })
                continue
            
            # Thêm thông tin vào kết quả
            logger.info(f"Successfully processed article for category {category_name}: {article_result['url']}")
            result['success'] += 1
            result['categories'].append({
                'id': category_id,
                'name': category_name,
                'slug': category.get('slug'),
                'status': 'success',
                'url': article_result['url'],
                'title': article_result['title'],
                'content_length': article_result['content_length'],
                'json_filepath': article_result['json_filepath']
            })
            
        logger.info(f"Processed all categories. Success: {result['success']}, Failed: {result['failed']}")
        return result
            
    except Exception as e:
        logger.error(f"Error processing categories: {str(e)}")
        return result

def save_article_to_json(category_id, category_name, article_url, article_data):
    """
    Lưu thông tin bài viết vào file JSON.
    
    Args:
        category_id (int): ID của danh mục
        category_name (str): Tên danh mục
        article_url (str): URL của bài viết
        article_data (dict): Dữ liệu bài viết gồm title và content
        
    Returns:
        str: Đường dẫn đến file JSON đã lưu
    """
    try:
        # Tạo thư mục output nếu chưa tồn tại
        if not os.path.exists(OUTPUT_DIR):
            os.makedirs(OUTPUT_DIR)
            logger.info(f"Created output directory: {OUTPUT_DIR}")
        
        # Tạo tên file dựa trên thời gian và danh mục
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        sanitized_name = re.sub(r'[^\w\-_]', '_', category_name)
        filename = f"{OUTPUT_DIR}/{sanitized_name}_{category_id}_{timestamp}.json"
        
        # Chuẩn bị dữ liệu JSON
        article_json = {
            "category_id": category_id,
            "category_name": category_name,
            "url": article_url,
            "title": article_data.get("title", ""),
            "content": article_data.get("content", ""),
            "scraped_at": datetime.now().isoformat()
        }
        
        # Ghi file JSON
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(article_json, f, ensure_ascii=False, indent=4)
            
        logger.info(f"Saved article to JSON file: {filename}")
        return filename
        
    except Exception as e:
        logger.error(f"Error saving article to JSON: {str(e)}")
        return None

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
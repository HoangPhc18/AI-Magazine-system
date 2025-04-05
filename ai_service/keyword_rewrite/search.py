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

if __name__ == '__main__':
    if len(sys.argv) > 1:
        keyword = sys.argv[1]
        url = search_google_news(keyword)
        if url:
            print(f"Found URL: {url}")
        else:
            print(f"No URL found for keyword: {keyword}")
    else:
        print("Usage: python search.py <keyword>") 
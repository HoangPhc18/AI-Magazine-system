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
        
        # Thêm -site:laodong.vn để loại trừ trang này khỏi kết quả
        search_query = f"{keyword} -site:laodong.vn -site:baomoi.com when:1d"
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
                        # Chuyển đổi URL tương đối thành tuyệt đối
                        article_url = "https://news.google.com/" + link['href'].lstrip('./')
                        
                        try:
                            # Truy cập URL để lấy URL thực tế
                            driver.get(article_url)
                            
                            # Chờ chuyển hướng hoàn thành
                            time.sleep(2)
                            
                            # Lấy URL hiện tại (URL thực tế của bài viết)
                            actual_url = driver.current_url
                            
                            # Kiểm tra xem URL có thuộc trang bị blacklist không
                            if not is_blacklisted_domain(actual_url):
                                logger.info(f"Found article URL: {actual_url}")
                                return actual_url
                            else:
                                logger.warning(f"Skipping blacklisted URL: {actual_url}")
                                continue
                                
                        except Exception as e:
                            logger.error(f"Error getting actual URL: {str(e)}")
                            continue
            
            logger.warning("No valid article links found in search results")
        else:
            logger.warning("No articles found in search results")
        
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

def is_blacklisted_domain(url):
    """
    Kiểm tra xem URL có thuộc các trang web bị blacklist không.
    
    Args:
        url (str): URL để kiểm tra
        
    Returns:
        bool: True nếu URL thuộc trang web bị blacklist, False nếu không
    """
    if not url:
        return False
        
    # Danh sách các domain bị blacklist do không thể trích xuất nội dung
    blacklisted_domains = [
        "laodong.vn",
        "baomoi.com",
        "facebook.com",
        "youtube.com",
        "tiktok.com"
    ]
    
    domain = urlparse(url).netloc.lower()
    
    for blacklisted in blacklisted_domains:
        if blacklisted in domain:
            logger.warning(f"URL {url} is from blacklisted domain: {blacklisted}")
            return True
            
    return False

def search_with_requests(keyword):
    """
    Tìm kiếm trên Google News bằng HTTP requests và trả về URL bài viết đầu tiên.
    
    Args:
        keyword (str): Từ khóa tìm kiếm
        
    Returns:
        str: URL của bài viết đầu tiên, hoặc None nếu không tìm thấy
    """
    # Kết cấu URL tìm kiếm Google News
    search_url = f"https://news.google.com/search?q={quote_plus(keyword)}&hl=vi&gl=VN&ceid=VN:vi"
    
    headers = {
        "User-Agent": random.choice(USER_AGENTS),
        "Accept-Language": "vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7",
    }
    
    try:
        logger.info(f"Sending request to Google News: {search_url}")
        response = requests.get(search_url, headers=headers, timeout=10)
        
        if response.status_code == 200:
            soup = BeautifulSoup(response.text, 'html.parser')
            
            # Tìm bài viết đầu tiên
            article_links = soup.select('a[href^="./articles/"]')
            
            found_urls = []
            for link in article_links:
                # Chuyển đổi URL tương đối thành tuyệt đối
                article_path = link['href'].replace('./', 'https://news.google.com/')
                
                # Trích xuất URL thực từ URL Google News
                try:
                    article_response = requests.head(article_path, headers=headers, timeout=5, allow_redirects=True)
                    actual_url = article_response.url
                    
                    # Kiểm tra xem URL có bị blacklist không
                    if not is_blacklisted_domain(actual_url):
                        found_urls.append(actual_url)
                        logger.info(f"Found article URL: {actual_url}")
                        
                    # Trả về URL đầu tiên không bị blacklist
                    if found_urls:
                        return found_urls[0]
                        
                except Exception as e:
                    logger.error(f"Error resolving article URL: {str(e)}")
            
            if not found_urls:
                logger.warning("No valid article URLs found in Google News")
        else:
            logger.error(f"Failed to fetch Google News: {response.status_code}")
        
        return None
        
    except Exception as e:
        logger.error(f"Error in Google News search: {str(e)}")
        return None

def direct_news_search(keyword):
    """
    Tìm kiếm trực tiếp trên Google News thông qua Google Search.
    
    Args:
        keyword (str): Từ khóa tìm kiếm
        
    Returns:
        list: Danh sách URLs của các bài viết tin tức
    """
    user_agent = random.choice(USER_AGENTS)
    headers = {
        "User-Agent": user_agent,
        "Accept-Language": "vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7",
    }
    
    # Tạo URL tìm kiếm với tham số tbm=nws cho News
    search_url = f"https://www.google.com/search?q={quote_plus(keyword)}&tbm=nws"
    logger.info(f"Searching with Google Search: {search_url}")
    
    results = []
    
    try:
        response = requests.get(search_url, headers=headers, timeout=10)
        response.raise_for_status()
        
        # Lưu phản hồi vào file để debug nếu cần
        with open("google_search_response.html", "w", encoding="utf-8") as f:
            f.write(response.text)
        logger.info("Saved Google Search response to google_search_response.html")
        
        soup = BeautifulSoup(response.text, 'html.parser')
        
        # Thử một số trình chọn CSS khác nhau để tìm kết quả
        selectors_to_try = [
            'div.g a',  # Định dạng cũ
            'a[href^="https://"]',  # Tất cả liên kết bắt đầu bằng https://
            '.WlydOe',  # Lớp CSS có thể được sử dụng cho kết quả tìm kiếm
            '.fP1Qef'   # Lớp CSS khác có thể được sử dụng cho kết quả tìm kiếm
        ]
        
        for selector in selectors_to_try:
            article_links = soup.select(selector)
            logger.info(f"Found {len(article_links)} results with selector '{selector}'")
            
            # Lọc liên kết
            for link in article_links:
                href = link.get('href')
                if href and href.startswith('http') and 'google.com' not in href:
                    # Loại bỏ tham số theo dõi nếu có
                    clean_url = href.split('&sa=')[0].split('?sa=')[0]
                    
                    # Kiểm tra URL có bị blacklist không
                    if not is_blacklisted_domain(clean_url) and clean_url not in results:
                        results.append(clean_url)
                        logger.info(f"Found valid article URL: {clean_url}")
            
            # Nếu đã tìm thấy ít nhất một kết quả, dừng lại
            if results:
                logger.info(f"Found {len(results)} valid article URLs from Google Search")
                return results
        
        # Thử tìm kiếm thêm với từ khóa loại trừ các trang bị blacklist
        if not results:
            exclude_terms = ' '.join(f'-site:{domain}' for domain in ['laodong.vn', 'baomoi.com'])
            refined_search_url = f"https://www.google.com/search?q={quote_plus(keyword)} {exclude_terms}&tbm=nws"
            logger.info(f"Trying refined search with exclusions: {refined_search_url}")
            
            try:
                refined_response = requests.get(refined_search_url, headers=headers, timeout=10)
                refined_soup = BeautifulSoup(refined_response.text, 'html.parser')
                
                for selector in selectors_to_try:
                    article_links = refined_soup.select(selector)
                    for link in article_links:
                        href = link.get('href')
                        if href and href.startswith('http') and 'google.com' not in href:
                            clean_url = href.split('&sa=')[0].split('?sa=')[0]
                            
                            if not is_blacklisted_domain(clean_url) and clean_url not in results:
                                results.append(clean_url)
                                logger.info(f"Found valid article URL from refined search: {clean_url}")
                
                if results:
                    return results
            except Exception as e:
                logger.error(f"Error in refined search: {str(e)}")
        
        logger.warning("No valid article URLs found in Google Search results")
        return []
        
    except Exception as e:
        logger.error(f"Error in direct_news_search: {str(e)}")
        return []

def search_google_news(keyword, skip=0):
    """
    Tìm kiếm trên Google News và trả về URL bài viết.
    
    Args:
        keyword (str): Từ khóa tìm kiếm
        skip (int): Số lượng kết quả đầu tiên cần bỏ qua (mặc định: 0)
        
    Returns:
        str: URL của bài viết, hoặc None nếu không tìm thấy
    """
    logger.info(f"Searching for keyword: '{keyword}' and non-accented version: '{remove_vietnamese_accents(keyword)}' (skip: {skip})")
    
    # Danh sách urls đã tìm thấy
    found_urls = []
    
    # Tìm kiếm với từ khóa gốc
    logger.info(f"Trying to search with keyword: '{keyword}'")
    
    # Thử phương pháp 1: Sử dụng HTTP requests (nhanh hơn)
    try:
        logger.info(f"Using HTTP request method for: '{keyword}'")
        url = search_with_requests(keyword)
        if url and "laodong.vn" not in url:  # Loại bỏ URL từ laodong.vn
            found_urls.append(url)
        elif url and "laodong.vn" in url:
            logger.warning(f"Skipping laodong.vn URL: {url}")
    except Exception as e:
        logger.error(f"Error with HTTP request method: {str(e)}")
    
    # Nếu có ít nhất (skip + 1) URL, trả về URL thứ skip
    if len(found_urls) > skip:
        return found_urls[skip]
    
    # Thử phương pháp 2: Tìm kiếm trực tiếp trên Google Search
    try:
        logger.info("Trying direct Google Search as it's more reliable")
        urls = direct_news_search(keyword)
        for url in urls:
            if "laodong.vn" not in url and url not in found_urls:  # Loại bỏ URL từ laodong.vn
                found_urls.append(url)
            elif "laodong.vn" in url:
                logger.warning(f"Skipping laodong.vn URL: {url}")
                
        # Nếu đủ số lượng URL cần bỏ qua, trả về URL tiếp theo
        if len(found_urls) > skip:
            return found_urls[skip]
    except Exception as e:
        logger.error(f"Error with direct Google Search: {str(e)}")
    
    # Nếu không tìm thấy với từ khóa gốc và cần bỏ qua kết quả, thử với từ khóa không dấu
    if skip > 0 or not found_urls:
        no_accent_keyword = remove_vietnamese_accents(keyword)
        if no_accent_keyword != keyword:
            logger.info(f"Trying with non-accented keyword: '{no_accent_keyword}'")
            
            try:
                # Tìm kiếm với HTTP requests
                url = search_with_requests(no_accent_keyword)
                if url and "laodong.vn" not in url and url not in found_urls:  # Loại bỏ URL từ laodong.vn
                    found_urls.append(url)
                elif url and "laodong.vn" in url:
                    logger.warning(f"Skipping laodong.vn URL: {url}")
            except Exception as e:
                logger.error(f"Error with HTTP request for non-accented keyword: {str(e)}")
            
            # Thử với Google Search
            try:
                urls = direct_news_search(no_accent_keyword)
                for url in urls:
                    if "laodong.vn" not in url and url not in found_urls:  # Loại bỏ URL từ laodong.vn
                        found_urls.append(url)
                    elif "laodong.vn" in url:
                        logger.warning(f"Skipping laodong.vn URL: {url}")
            except Exception as e:
                logger.error(f"Error with direct Google Search for non-accented keyword: {str(e)}")
    
    # Trả về URL với số thứ tự skip nếu có
    if len(found_urls) > skip:
        return found_urls[skip]
    
    # Cuối cùng, thử với Selenium nếu các phương pháp khác thất bại và chỉ nếu chưa bỏ qua
    if skip == 0:
        try:
            logger.info("Fallback to Selenium method as last resort")
            url = search_with_selenium(keyword)
            if url and "laodong.vn" not in url:  # Loại bỏ URL từ laodong.vn
                return url
            elif url and "laodong.vn" in url:
                logger.warning(f"Skipping laodong.vn URL from Selenium: {url}")
                # Thử tìm một URL khác bằng Selenium
                logger.info("Trying to find another URL with Selenium")
                url = search_with_selenium(keyword + " -site:laodong.vn")
                if url:
                    return url
        except Exception as e:
            logger.error(f"Error with Selenium method: {str(e)}")
    
    # Không tìm thấy URL phù hợp
    logger.error(f"Failed to find article URL after trying all methods (skip: {skip})")
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
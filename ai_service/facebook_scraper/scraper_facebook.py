from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from webdriver_manager.chrome import ChromeDriverManager
from bs4 import BeautifulSoup
import time
import os
import mysql.connector
import re
import dotenv
import psutil
import subprocess
import argparse
import logging
from datetime import datetime
import uuid
import sys

# Thiết lập logging
logging.basicConfig(level=logging.INFO, 
                   format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
logger = logging.getLogger('facebook_scraper')

# Load .env file from Laravel backend
dotenv_path = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), 'backend', '.env')
dotenv.load_dotenv(dotenv_path)

def get_db_connection():
    """Get a connection to the Laravel database"""
    db_host = os.getenv('DB_HOST', 'localhost')
    db_name = os.getenv('DB_DATABASE', 'AiMagazineDB')
    db_user = os.getenv('DB_USERNAME', 'root')
    db_pass = os.getenv('DB_PASSWORD', '')
    db_port = int(os.getenv('DB_PORT', '3306'))
    
    return mysql.connector.connect(
        host=db_host,
        user=db_user,
        password=db_pass,
        database=db_name,
        port=db_port
    )

def is_chrome_running():
    """Kiểm tra xem Chrome có đang chạy không"""
    for proc in psutil.process_iter(['name']):
        if 'chrome' in proc.info['name'].lower():
            return True
    return False

def kill_chrome_processes():
    """Tắt tất cả các tiến trình Chrome đang chạy"""
    os.system('taskkill /f /im chrome.exe')

def setup_mysql_connection():
    """Thiết lập kết nối đến MySQL"""
    try:
        # Lấy thông tin kết nối từ biến môi trường
        db_host = os.getenv('DB_HOST', 'localhost')
        db_user = os.getenv('DB_USERNAME', 'root')
        db_password = os.getenv('DB_PASSWORD', '')
        db_name = os.getenv('DB_DATABASE', 'AiMagazineDB')
        
        # Kết nối đến cơ sở dữ liệu
        connection = mysql.connector.connect(
            host=db_host,
            user=db_user,
            password=db_password,
            database=db_name
        )
        
        if connection.is_connected():
            logger.info(f"Đã kết nối đến cơ sở dữ liệu MySQL: {db_name}")
            return connection
        
    except Exception as e:
        logger.error(f"Lỗi kết nối đến MySQL: {e}")
        return None

def setup_driver(headless=True, use_profile=False, chrome_profile="Default"):
    """Thiết lập trình duyệt Chrome với các tùy chọn"""
    chrome_options = Options()
    
    if headless:
        chrome_options.add_argument("--headless=new")
    
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--window-size=1920,1080")
    chrome_options.add_argument("--log-level=3")  # Chỉ hiển thị lỗi nghiêm trọng
    
    # Sử dụng profile Chrome nếu được yêu cầu
    if use_profile:
        user_data_dir = os.path.expanduser(f"~\\AppData\\Local\\Google\\Chrome\\User Data")
        chrome_options.add_argument(f"--user-data-dir={user_data_dir}")
        chrome_options.add_argument(f"--profile-directory={chrome_profile}")
    
    try:
        # Sửa: Tạo service trước và thử các cách khác nhau để khởi tạo ChromeDriver
        try:
            # Cách 1: Sử dụng Service với ChromeDriverManager
            service = Service(ChromeDriverManager().install())
            driver = webdriver.Chrome(service=service, options=chrome_options)
        except Exception as e:
            logger.warning(f"Không thể khởi tạo driver theo cách thông thường: {e}")
            try:
                # Cách 2: Sử dụng ChromeDriverManager trực tiếp
                driver = webdriver.Chrome(ChromeDriverManager().install(), options=chrome_options)
            except Exception as e2:
                logger.warning(f"Không thể khởi tạo driver với ChromeDriverManager: {e2}")
                try:
                    # Cách 3: Không sử dụng ChromeDriverManager
                    driver = webdriver.Chrome(options=chrome_options)
                except Exception as e3:
                    logger.error(f"Tất cả các cách khởi tạo ChromeDriver đều thất bại: {e3}")
                    raise
        
        logger.info("Đã khởi tạo thành công trình duyệt Chrome")
        return driver
    except Exception as e:
        logger.error(f"Lỗi khi khởi tạo trình duyệt Chrome: {e}")
        raise

def get_facebook_posts(url, chrome_user_data_dir=None, chrome_profile="Default", limit=10, save_to_db=False, use_profile=True, headless=True):
    # Sử dụng hàm setup_driver thay vì khởi tạo driver thủ công
    driver = None
    posts = []
    
    try:
        # Khởi tạo trình duyệt sử dụng hàm setup_driver
        driver = setup_driver(headless=headless, use_profile=use_profile, chrome_profile=chrome_profile)
        
        # Truy cập URL
        logger.info(f"Truy cập URL: {url}")
        driver.get(url)
        
        # Đợi tải trang
        time.sleep(5)
        
        # Kiểm tra xem có phải URL group hay page
        is_group = "groups" in url
        is_page = not is_group
        
        # Scroll để tải thêm bài viết
        logger.info("Bắt đầu scroll để tải thêm bài viết")
        scroll_and_collect_posts(driver, limit)
        
        # Thu thập dữ liệu các bài viết
        logger.info("Thu thập dữ liệu bài viết")
        posts = extract_posts_data(driver, is_group)
        
        # Lưu vào cơ sở dữ liệu nếu cần
        if save_to_db and posts:
            logger.info(f"Lưu {len(posts)} bài viết vào cơ sở dữ liệu")
            save_posts_to_database(posts)
        
        return posts
    except Exception as e:
        logger.error(f"Lỗi trong quá trình lấy bài viết: {str(e)}")
        raise
    finally:
        if driver:
            logger.info("Đóng trình duyệt")
            driver.quit()

def scroll_and_collect_posts(driver, limit=10):
    """Scroll trang để tải thêm bài viết"""
    current_height = 0
    max_scrolls = 10  # Giới hạn số lần scroll để tránh vòng lặp vô hạn
    scroll_count = 0
    
    try:
        # Scroll xuống để tải thêm bài viết
        while scroll_count < max_scrolls:
            # Scroll xuống cuối trang
            driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
            time.sleep(2)  # Đợi tải nội dung
            
            # Kiểm tra nếu đã cuộn đến cuối trang
            new_height = driver.execute_script("return document.body.scrollHeight")
            if new_height == current_height:
                # Đã đến cuối trang, thử nhấn "Xem thêm" nếu có
                try:
                    see_more_buttons = driver.find_elements(By.XPATH, "//span[contains(text(), 'Xem thêm')]")
                    if see_more_buttons:
                        for btn in see_more_buttons[:3]:  # Chỉ nhấn 3 nút đầu tiên
                            driver.execute_script("arguments[0].click();", btn)
                            time.sleep(1)
                        current_height = 0  # Reset để tiếp tục scroll
                    else:
                        # Không còn nút Xem thêm, thoát vòng lặp
                        break
                except Exception as e:
                    logger.warning(f"Lỗi khi nhấn nút Xem thêm: {str(e)}")
                    break
            
            current_height = new_height
            scroll_count += 1
            logger.info(f"Đã scroll lần {scroll_count}/{max_scrolls}")
            
            # Kiểm tra xem đã có đủ bài viết chưa
            posts = driver.find_elements(By.XPATH, "//div[@data-ad-preview='message']")
            if len(posts) >= limit:
                logger.info(f"Đã tìm thấy {len(posts)} bài viết, đủ số lượng yêu cầu ({limit})")
                break
        
        # Mở rộng các phần "Xem thêm" trong nội dung bài viết
        see_more_links = driver.find_elements(By.XPATH, "//div[contains(text(), 'Xem thêm')]")
        logger.info(f"Tìm thấy {len(see_more_links)} liên kết 'Xem thêm' trong nội dung")
        
        for link in see_more_links:
            try:
                driver.execute_script("arguments[0].click();", link)
                time.sleep(0.3)
            except Exception as e:
                logger.warning(f"Không thể nhấn vào liên kết 'Xem thêm': {str(e)}")
                continue
    
    except Exception as e:
        logger.error(f"Lỗi khi scroll trang: {str(e)}")

def extract_posts_data(driver, is_group=False):
    """Extract dữ liệu bài viết từ trang Facebook"""
    posts = []
    
    try:
        # Xác định tên page hoặc group
        page_name = ""
        try:
            name_element = driver.find_element(By.XPATH, "//h1")
            if name_element:
                page_name = name_element.text.strip()
                logger.info(f"Tên page/group: {page_name}")
        except Exception as e:
            logger.warning(f"Không thể lấy tên page/group: {str(e)}")
        
        # Lấy URL hiện tại
        current_url = driver.current_url
        
        # Tìm tất cả các bài viết
        post_elements = driver.find_elements(By.XPATH, "//div[@data-ad-preview='message']")
        logger.info(f"Tìm thấy {len(post_elements)} bài viết")
        
        for post_element in post_elements:
            try:
                # Lấy nội dung bài viết
                content = post_element.text.strip()
                
                if content:
                    post_data = {
                        'content': content,
                        'source_url': current_url,
                        'page_or_group_name': page_name,
                        'is_group': is_group
                    }
                    posts.append(post_data)
            except Exception as e:
                logger.warning(f"Lỗi khi xử lý bài viết: {str(e)}")
                continue
    
    except Exception as e:
        logger.error(f"Lỗi khi extract dữ liệu bài viết: {str(e)}")
    
    return posts

def save_posts_to_database(posts):
    """Lưu bài viết vào cơ sở dữ liệu"""
    connection = None
    
    try:
        # Thiết lập kết nối đến cơ sở dữ liệu
        connection = setup_mysql_connection()
        
        if not connection:
            logger.error("Không thể kết nối đến cơ sở dữ liệu")
            return False
        
        cursor = connection.cursor()
        
        # Lưu từng bài viết vào cơ sở dữ liệu
        for post in posts:
            content = post.get('content', '')
            source_url = post.get('source_url', '')
            page_or_group_name = post.get('page_or_group_name', '')
            
            # Thực hiện truy vấn SQL để lưu bài viết
            query = """
                INSERT INTO facebook_posts 
                (content, source_url, page_or_group_name, processed, created_at, updated_at) 
                VALUES (%s, %s, %s, %s, NOW(), NOW())
            """
            
            cursor.execute(query, (content, source_url, page_or_group_name, False))
        
        # Commit các thay đổi
        connection.commit()
        logger.info(f"Đã lưu {len(posts)} bài viết vào cơ sở dữ liệu")
        
        return True
    
    except Exception as e:
        logger.error(f"Lỗi khi lưu bài viết vào cơ sở dữ liệu: {str(e)}")
        
        if connection:
            connection.rollback()
        
        return False
    
    finally:
        if connection and connection.is_connected():
            cursor.close()
            connection.close()

# ========== Khi gọi từ dòng lệnh ============
if __name__ == "__main__":
    # Parse command line arguments
    parser = argparse.ArgumentParser(description='Facebook Post Scraper')
    parser.add_argument('--url', required=True, help='Facebook URL to scrape')
    parser.add_argument('--save_to_db', default='false', choices=['true', 'false'], help='Save posts to database')
    parser.add_argument('--headless', default='true', choices=['true', 'false'], help='Run browser in headless mode')
    parser.add_argument('--use_profile', default='true', choices=['true', 'false'], help='Use Chrome profile')
    parser.add_argument('--chrome_profile', default='Default', help='Chrome profile to use')
    parser.add_argument('--limit', type=int, default=10, help='Maximum number of posts to scrape')

    args = parser.parse_args()
    
    # Convert string arguments to boolean
    save_to_db = args.save_to_db.lower() == 'true'
    headless = args.headless.lower() == 'true'
    use_profile = args.use_profile.lower() == 'true'
    
    try:
        logger.info(f"Bắt đầu thu thập bài viết từ URL: {args.url}")
        
        # Gọi hàm thu thập bài viết
        posts = get_facebook_posts(
            url=args.url,
            limit=args.limit,
            save_to_db=save_to_db,
            use_profile=use_profile,
            chrome_profile=args.chrome_profile,
            headless=headless
        )
        
        if posts:
            logger.info(f"Đã thu thập được {len(posts)} bài viết từ {args.url}")
            
            # In các bài viết ra nếu không lưu vào database
            if not save_to_db:
                for i, post in enumerate(posts, 1):
                    print(f"\n--- Bài viết {i} ---")
                    print(post.get('content', ''))
                    print("-" * 50)
        else:
            logger.warning(f"Không thu thập được bài viết nào từ {args.url}")
    
    except Exception as e:
        logger.error(f"Lỗi: {str(e)}")
        sys.exit(1)
    
    sys.exit(0)

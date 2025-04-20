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
import json
import pickle
import platform

# Tạo thư mục cookies nếu chưa tồn tại
os.makedirs("cookies", exist_ok=True)

# Thiết lập logging
logging.basicConfig(level=logging.INFO, 
                   format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
logger = logging.getLogger('facebook_scraper')

# Load .env file from Laravel backend
dotenv_path = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), 'backend', '.env')
dotenv.load_dotenv(dotenv_path)

# Load Facebook credentials from .env
FB_USERNAME = os.getenv('FACEBOOK_USERNAME', '')
FB_PASSWORD = os.getenv('FACEBOOK_PASSWORD', '')
USE_CHROME_PROFILE = os.getenv('USE_CHROME_PROFILE', 'false').lower() == 'true'
CHROME_PROFILE_PATH = os.getenv('CHROME_PROFILE_PATH', '/app/chrome_profile')
HEADLESS = os.getenv('HEADLESS', 'true').lower() == 'true'

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

def save_cookies(driver):
    """Save browser cookies to a file"""
    try:
        # Create cookies directory if it doesn't exist
        os.makedirs("cookies", exist_ok=True)
        
        # Save cookies to a file
        with open("cookies/facebook_cookies.json", "w") as file:
            json.dump(driver.get_cookies(), file)
            
        logger.info("Successfully saved cookies")
        return True
    except Exception as e:
        logger.error(f"Error saving cookies: {str(e)}")
        return False
        
def load_cookies(driver):
    """Load cookies from file and add them to the browser"""
    try:
        cookie_file = "cookies/facebook_cookies.json"
        
        if not os.path.exists(cookie_file):
            logger.warning("Cookie file not found")
            return False
            
        # Load cookies from file
        with open(cookie_file, "r") as file:
            cookies = json.load(file)
            
        # Navigate to Facebook domain first (required to set cookies)
        driver.get("https://www.facebook.com")
        time.sleep(2)
        
        # Check if cookies are in EditThisCookie format (array of objects with id field)
        if isinstance(cookies, list) and len(cookies) > 0 and 'id' in cookies[0]:
            logger.info("Detected EditThisCookie format")
            # Convert from EditThisCookie format
            for cookie in cookies:
                try:
                    # Remove fields that Selenium doesn't accept
                    cookie_dict = {
                        'name': cookie.get('name'),
                        'value': cookie.get('value'),
                        'domain': cookie.get('domain'),
                        'path': cookie.get('path'),
                        'expiry': cookie.get('expirationDate'),
                        'secure': cookie.get('secure'),
                        'httpOnly': cookie.get('httpOnly')
                    }
                    # Remove None values
                    cookie_dict = {k: v for k, v in cookie_dict.items() if v is not None}
                    driver.add_cookie(cookie_dict)
                except Exception as e:
                    logger.warning(f"Error adding cookie: {str(e)}")
        else:
            # Standard format - just add directly
            for cookie in cookies:
                try:
                    driver.add_cookie(cookie)
                except Exception as e:
                    logger.warning(f"Error adding cookie: {str(e)}")
                
        # Refresh page to apply cookies
        driver.get("https://www.facebook.com")
        time.sleep(3)
        
        # Check if login was successful
        if "facebook.com/login" in driver.current_url:
            logger.warning("Cookie login failed, still on login page")
            return False
            
        logger.info("Successfully loaded cookies")
        return True
    except Exception as e:
        logger.error(f"Error loading cookies: {str(e)}")
        return False

def login_facebook(driver, username=None, password=None):
    """Login to Facebook using cookies or credentials"""
    try:
        # First try to login using cookies
        cookie_login_success = False
        logger.info("Attempting login with cookies")
        
        if load_cookies(driver):
            # Navigate to Facebook homepage with cookies loaded
            driver.get("https://www.facebook.com")
            time.sleep(3)
            
            # Take screenshot for debugging
            try:
                os.makedirs("logs", exist_ok=True)
                screenshot_path = "logs/facebook_cookie_login.png"
                driver.save_screenshot(screenshot_path)
                logger.info(f"Saved login screenshot to {screenshot_path}")
            except Exception as e:
                logger.warning(f"Could not save screenshot: {str(e)}")
            
            # Check if login was successful by looking for common elements
            try:
                # Check if we're on the login page
                if "facebook.com/login" in driver.current_url:
                    logger.warning("Still on login page after loading cookies")
                    cookie_login_success = False
                else:
                    # Check for elements that would indicate we're logged in
                    if driver.find_elements(By.ID, "facebook"):
                        if driver.find_elements(By.CSS_SELECTOR, '[aria-label="Home"]') or \
                           driver.find_elements(By.CSS_SELECTOR, '[aria-label="Facebook"]') or \
                           driver.find_elements(By.CSS_SELECTOR, '[aria-label="Your profile"]') or \
                           driver.find_elements(By.CSS_SELECTOR, '[data-pagelet="Stories"]') or \
                           driver.find_elements(By.CSS_SELECTOR, '[role="navigation"]'):
                            logger.info("Successfully logged in using cookies")
                            cookie_login_success = True
                            
                            # Save screenshot of success
                            try:
                                os.makedirs("logs", exist_ok=True)
                                driver.save_screenshot("logs/facebook_login_success.png")
                            except:
                                pass
                            
                            return True
            except Exception as e:
                logger.warning(f"Error checking login status: {str(e)}")
                
        # If cookie login failed and credentials are provided, try normal login
        if not cookie_login_success and username and password:
            logger.info("Attempting login with credentials")
            driver.get("https://www.facebook.com")
            time.sleep(2)
            
            # Save screenshot before login
            try:
                os.makedirs("logs", exist_ok=True)
                driver.save_screenshot("logs/facebook_before_login.png")
            except:
                pass
            
            # Enter username and password
            try:
                # Enter email/username
                email_field = WebDriverWait(driver, 10).until(
                    EC.presence_of_element_located((By.ID, "email"))
                )
                email_field.clear()
                email_field.send_keys(username)
                
                # Enter password
                password_field = WebDriverWait(driver, 10).until(
                    EC.presence_of_element_located((By.ID, "pass"))
                )
                password_field.clear()
                password_field.send_keys(password)
                
                # Click login button
                login_button = WebDriverWait(driver, 10).until(
                    EC.element_to_be_clickable((By.NAME, "login"))
                )
                login_button.click()
                
                # Wait for login to complete
                time.sleep(5)
                
                # Check if we're still on login page
                if "facebook.com/login" in driver.current_url:
                    logger.warning("Still on login page after credential login")
                    
                    # Save screenshot for debugging
                    try:
                        driver.save_screenshot("logs/facebook_login_failed.png")
                    except:
                        pass
                    
                    return False
                    
                # Save screenshot after login
                try:
                    driver.save_screenshot("logs/facebook_after_login.png")
                except:
                    pass
                
                # Save cookies after successful login
                save_cookies(driver)
                logger.info("Successfully logged in with credentials")
                return True
            except Exception as e:
                logger.error(f"Login failed: {str(e)}")
                return False
        
        return cookie_login_success
    except Exception as e:
        logger.error(f"Error in login process: {str(e)}")
        return False

def setup_driver(headless=None, use_profile=None, chrome_profile="Default"):
    """Setup Chrome driver with options"""
    try:
        # Sử dụng biến môi trường nếu không được cung cấp
        if headless is None:
            headless = HEADLESS
        if use_profile is None:
            use_profile = USE_CHROME_PROFILE
            
        logger.info(f"Setting up Chrome driver: headless={headless}, use_profile={use_profile}, chrome_profile={chrome_profile}")
        
        options = webdriver.ChromeOptions()
        
        # Use Chrome profile if requested
        if use_profile:
            # Sử dụng đường dẫn từ biến môi trường
            user_data_dir = CHROME_PROFILE_PATH
            logger.info(f"Using Chrome profile at: {user_data_dir}")
            
            options.add_argument(f"user-data-dir={user_data_dir}")
            options.add_argument(f"profile-directory={chrome_profile}")
            
        # Common options
        options.add_argument("--disable-notifications")
        options.add_argument("--disable-infobars")
        options.add_argument("--disable-extensions")
        options.add_argument("--disable-gpu")
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        
        # Add user agent
        options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36")
        
        # Run in headless mode if requested
        if headless:
            options.add_argument("--headless=new")
        
        # Sử dụng webdriver-manager để tự động tải và quản lý ChromeDriver
        from webdriver_manager.chrome import ChromeDriverManager
        from selenium.webdriver.chrome.service import Service
        
        # Initialize the WebDriver with automatic ChromeDriver management
        service = Service(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service, options=options)
        driver.set_window_size(1920, 1080)
        
        logger.info("Successfully initialized Chrome driver")
        return driver
    except Exception as e:
        logger.error(f"Error setting up Chrome driver: {str(e)}")
        return None

def get_facebook_posts(url, limit=10, headless=True, use_profile=False, chrome_profile="Default", save_to_db=False, username=None, password=None):
    """Get posts from Facebook URL"""
    driver = None
    attempts = 0
    max_attempts = 3
    
    # Use environment variables if not provided
    if username is None:
        username = FB_USERNAME
    if password is None:
        password = FB_PASSWORD
    
    logger.info(f"Will scrape URL: {url} with limit: {limit}")
    logger.info(f"Using profile: {use_profile}, Headless mode: {headless}")
    logger.info(f"Username provided: {'Yes' if username else 'No'}, Password provided: {'Yes' if password else 'No'}")
    
    while attempts < max_attempts:
        attempts += 1
        try:
            # Setup Selenium driver
            driver = setup_driver(headless=headless, use_profile=use_profile, chrome_profile=chrome_profile)
            if not driver:
                logger.error("Không thể khởi tạo trình duyệt Chrome")
                continue
                
            # Check if URL is for a Facebook group
            is_group = "groups" in url
            
            # Attempt to login to Facebook
            login_success = False
            if not use_profile:
                login_success = login_facebook(driver, username, password)
                if not login_success:
                    logger.warning(f"Không thể đăng nhập (lần {attempts}/{max_attempts}). Thử truy cập URL mà không đăng nhập.")
                else:
                    logger.info("Đăng nhập Facebook thành công")
            
            # Navigate to the Facebook URL
            logger.info(f"Truy cập URL: {url}")
            driver.get(url)
            
            # Take screenshot for debugging
            try:
                os.makedirs("logs", exist_ok=True)
                screenshot_path = f"logs/facebook_url_{attempts}.png"
                driver.save_screenshot(screenshot_path)
                logger.info(f"Đã lưu ảnh chụp màn hình tại: {screenshot_path}")
            except Exception as e:
                logger.warning(f"Không thể lưu ảnh chụp màn hình: {str(e)}")
            
            # Kiểm tra xem có hiển thị trang đăng nhập không
            if "facebook.com/login" in driver.current_url and attempts < max_attempts:
                logger.warning(f"Bị chuyển hướng đến trang đăng nhập (lần {attempts}/{max_attempts}). Thử lại...")
                if driver:
                    driver.quit()
                continue
            
            # Wait for page to load
            time.sleep(5)
            
            # Scroll down to load more posts
            logger.info("Bắt đầu cuộn trang để tải bài viết...")
            scroll_count = 0
            max_scrolls = min(limit // 2 + 5, 20)  # Adjust scrolling based on limit
            
            # Lưu chiều cao trang hiện tại
            last_height = driver.execute_script("return document.body.scrollHeight")
            
            while scroll_count < max_scrolls:
                # Cuộn xuống
                driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
                time.sleep(3)  # Đợi lâu hơn cho nội dung tải
                
                # Tính chiều cao mới
                new_height = driver.execute_script("return document.body.scrollHeight")
                scroll_count += 1
                
                # Kiểm tra chiều cao trang có thay đổi không
                if new_height == last_height:
                    logger.info(f"Đã đạt đến cuối trang sau {scroll_count} lần cuộn")
                    
                    # Thử nhấp vào các nút "Xem thêm" nếu có
                    try:
                        see_more_buttons = driver.find_elements(By.XPATH, 
                            "//span[contains(text(), 'Xem thêm') or contains(text(), 'See more')]")
                        
                        if see_more_buttons:
                            logger.info(f"Tìm thấy {len(see_more_buttons)} nút 'Xem thêm'")
                            for btn in see_more_buttons[:5]:
                                try:
                                    driver.execute_script("arguments[0].click();", btn)
                                    time.sleep(1)
                                except:
                                    pass
                        else:
                            # Nếu không có nút "Xem thêm", thoát khỏi vòng lặp
                            logger.info("Không tìm thấy nút 'Xem thêm'. Kết thúc cuộn.")
                            break
                    except Exception as e:
                        logger.warning(f"Lỗi khi tìm/nhấp nút 'Xem thêm': {str(e)}")
                        break
                
                logger.info(f"Cuộn xuống {scroll_count}/{max_scrolls} lần")
                last_height = new_height
            
            # Extract post data
            logger.info("Bắt đầu trích xuất dữ liệu bài viết...")
            posts = extract_posts_data(driver, is_group)
            
            # Log the number of posts found
            logger.info(f"Đã tìm thấy {len(posts)} bài viết từ URL: {url}")
            
            # Limit the number of posts as requested
            posts = posts[:limit]
            
            # Check if any posts were found
            if not posts:
                logger.warning(f"Không tìm thấy bài viết nào (lần {attempts}/{max_attempts})")
                if driver:
                    driver.quit()
                if attempts < max_attempts:
                    logger.info(f"Thử lại lần {attempts+1}...")
                    time.sleep(5)  # Đợi một chút trước khi thử lại
                    continue
            else:
                # Save to database if requested
                if save_to_db and posts:
                    save_posts_to_database(posts)
                return posts
                
        except Exception as e:
            logger.error(f"Lỗi trong quá trình lấy bài viết (lần {attempts}/{max_attempts}): {str(e)}")
            if attempts < max_attempts:
                logger.info(f"Thử lại lần {attempts+1}...")
                time.sleep(5)  # Đợi một chút trước khi thử lại
            
        finally:
            if driver:
                driver.quit()
    
    # Nếu đã thử nhiều lần vẫn không thành công
    logger.error(f"Không thể lấy bài viết sau {max_attempts} lần thử")
    return []

def extract_posts_data(driver, is_group=False):
    """Extract post data from page"""
    posts = []
    selectors_tried = 0
    max_selectors = 5
    
    # Danh sách các XPath selector để thử
    post_selectors = [
        # Selector cho bài viết trong nhóm
        '//div[@role="article"]',
        # Selector cho bài viết trên trang/profile (phiên bản mới)
        '//div[contains(@class, "x1yztbdb") and contains(@class, "x1n2onr6")]',
        # Selector cho feed
        '//div[contains(@class, "x1lliihq")]//div[@role="article"]',
        # Selector cho bài viết cũ hơn
        '//div[contains(@data-pagelet, "FeedUnit")]',
        # Selector phổ biến
        '//div[contains(@class, "userContentWrapper")]'
    ]
    
    logger.info("Bắt đầu tìm bài viết với nhiều selector khác nhau...")
    
    # Thử nhiều selector khác nhau
    for selector in post_selectors:
        selectors_tried += 1
        try:
            post_elements = driver.find_elements(By.XPATH, selector)
            
            if post_elements:
                logger.info(f"Tìm thấy {len(post_elements)} bài viết với selector: {selector}")
                
                # Trích xuất dữ liệu từ các bài viết
                for idx, post in enumerate(post_elements[:50]):  # Giới hạn 50 bài viết
                    try:
                        # Cuộn đến bài viết để đảm bảo hiển thị
                        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", post)
                        time.sleep(0.3)
                        
                        # Thử trích xuất URL bài viết
                        post_url = ""
                        timestamp_selectors = [
                            './/a[contains(@href, "/posts/")]',
                            './/a[contains(@href, "/permalink/")]',
                            './/a[contains(@href, "story_fbid=")]',
                            './/a[contains(@href, "/photo.php")]',
                            './/a[contains(@href, "/video.php")]',
                            './/a[contains(@aria-label, "")]//span[contains(text(), "giờ") or contains(text(), "phút") or contains(text(), "h") or contains(text(), "d") or contains(text(), "hr")]/..'
                        ]
                        
                        for url_selector in timestamp_selectors:
                            try:
                                url_elements = post.find_elements(By.XPATH, url_selector)
                                if url_elements:
                                    post_url = url_elements[0].get_attribute("href")
                                    if post_url:
                                        # Loại bỏ tham số query từ URL
                                        post_url = post_url.split("?")[0]
                                        break
                            except:
                                continue
                        
                        # Trích xuất nội dung bài viết
                        content = ""
                        content_selectors = [
                            './/div[contains(@data-ad-comet-preview, "message")]',
                            './/div[contains(@class, "x1iorvi4") and contains(@class, "x1pi30zi")]',
                            './/div[@data-ad-preview="message"]',
                            './/div[contains(@class, "userContent")]',
                            './/span[contains(@class, "x193iq5w")]',
                            './/div[contains(@dir, "auto")]'
                        ]
                        
                        for content_selector in content_selectors:
                            try:
                                content_elements = post.find_elements(By.XPATH, content_selector)
                                if content_elements:
                                    content = content_elements[0].text
                                    if content:
                                        break
                            except:
                                continue
                        
                        # Trích xuất thời gian đăng
                        post_time = ""
                        time_selectors = [
                            './/span[contains(text(), "giờ") or contains(text(), "phút") or contains(text(), "Hôm qua") or contains(text(), "tháng")]',
                            './/span[contains(text(), "hr") or contains(text(), "min") or contains(text(), "Yesterday") or contains(text(), "month")]',
                            './/span[@aria-label and contains(@class, "x1i10hfl")]',
                            './/a//span[contains(@class, "x4k7w5x")]'
                        ]
                        
                        for time_selector in time_selectors:
                            try:
                                time_elements = post.find_elements(By.XPATH, time_selector)
                                if time_elements:
                                    post_time = time_elements[0].text
                                    if post_time:
                                        break
                            except:
                                continue
                        
                        # Kiểm tra xem thu thập được nội dung hữu ích không
                        if content or post_url:
                            # Tạo ID duy nhất nếu không có URL
                            post_id = post_url.split("/")[-1] if post_url and "/" in post_url else str(uuid.uuid4())
                            
                            # Thu thập thêm thông tin về trang
                            page_name = ""
                            try:
                                page_elements = driver.find_elements(By.XPATH, '//h1[contains(@class, "x1heor9g")]')
                                if page_elements:
                                    page_name = page_elements[0].text
                            except:
                                pass
                                
                            # Tạo đối tượng dữ liệu bài viết
                            post_data = {
                                "post_id": post_id,
                                "content": content,
                                "url": post_url,
                                "time": post_time,
                                "page_or_group_name": page_name,
                                "source_url": driver.current_url,
                                "scraped_at": datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                            }
                            
                            posts.append(post_data)
                            logger.info(f"Đã thêm bài viết {idx+1}: URL={post_url[:30] + '...' if len(post_url) > 30 else post_url}")
                    except Exception as e:
                        logger.warning(f"Lỗi khi xử lý bài viết: {str(e)}")
                        continue
                
                # Nếu đã tìm thấy bài viết, không cần thử selector khác
                if posts:
                    break
                    
            else:
                logger.info(f"Không tìm thấy bài viết với selector {selectors_tried}/{len(post_selectors)}: {selector}")
                
        except Exception as e:
            logger.warning(f"Lỗi khi sử dụng selector {selector}: {str(e)}")
    
    # Ghi log tổng kết
    if posts:
        logger.info(f"Đã trích xuất tổng cộng {len(posts)} bài viết sau khi thử {selectors_tried}/{len(post_selectors)} selector")
    else:
        logger.warning(f"Không thể trích xuất bài viết nào sau khi thử {selectors_tried}/{len(post_selectors)} selector")
    
    return posts

def save_posts_to_database(posts):
    """Save scraped posts to the database"""
    if not posts:
        logger.warning("Không có bài viết để lưu vào cơ sở dữ liệu")
        return
    
    try:
        # Connect to the database
        connection = get_db_connection()
        cursor = connection.cursor()
        
        # Prepare SQL query that matches the actual table structure
        insert_query = """
        INSERT INTO facebook_posts (content, source_url, page_or_group_name, processed, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
        content = VALUES(content),
        page_or_group_name = VALUES(page_or_group_name),
        updated_at = VALUES(updated_at)
        """
        
        # Insert each post
        success_count = 0
        now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        
        for post in posts:
            try:
                values = (
                    post.get("content", ""),
                    post.get("url", ""),  # Map to source_url field
                    post.get("page_or_group_name", ""),
                    0,  # processed = false
                    now,  # created_at
                    now   # updated_at
                )
                
                cursor.execute(insert_query, values)
                success_count += 1
            except Exception as e:
                logger.error(f"Lỗi khi chèn bài viết vào cơ sở dữ liệu: {str(e)}")
                continue
        
        # Commit the transaction
        connection.commit()
        logger.info(f"Đã lưu {success_count}/{len(posts)} bài viết vào cơ sở dữ liệu")
        
    except Exception as e:
        logger.error(f"Lỗi khi lưu bài viết vào cơ sở dữ liệu: {str(e)}")
    finally:
        if 'connection' in locals() and connection.is_connected():
            cursor.close()
            connection.close()
            logger.info("Đã đóng kết nối đến cơ sở dữ liệu")

def main():
    """Main function to run the scraper"""
    parser = argparse.ArgumentParser(description='Facebook Scraper')
    parser.add_argument('--url', type=str, required=True, help='Facebook URL to scrape')
    parser.add_argument('--limit', type=int, default=10, help='Limit number of posts to scrape')
    parser.add_argument('--headless', action='store_true', help='Run Chrome in headless mode')
    parser.add_argument('--use_profile', action='store_true', help='Use Chrome profile')
    parser.add_argument('--chrome_profile', type=str, default="Default", help='Chrome profile name')
    parser.add_argument('--save_to_db', action='store_true', help='Save posts to database')
    parser.add_argument('--username', type=str, help='Facebook username for login')
    parser.add_argument('--password', type=str, help='Facebook password for login')
    
    args = parser.parse_args()
    
    try:
        # Get posts from Facebook
        posts = get_facebook_posts(
            url=args.url,
            limit=args.limit,
            headless=args.headless,
            use_profile=args.use_profile,
            chrome_profile=args.chrome_profile,
            save_to_db=args.save_to_db,
            username=args.username,
            password=args.password
        )
        
        # Print results
        logger.info(f"Đã thu thập {len(posts)} bài viết")
        for i, post in enumerate(posts, 1):
            print(f"\n--- Post {i} ---")
            print(f"ID: {post.get('post_id', 'N/A')}")
            print(f"Time: {post.get('time', 'N/A')}")
            print(f"URL: {post.get('url', 'N/A')}")
            print(f"Content: {post.get('content', 'N/A')[:100]}...")
        
        return posts
    except Exception as e:
        logger.error(f"Lỗi trong hàm main: {str(e)}")
        return []

if __name__ == "__main__":
    main()

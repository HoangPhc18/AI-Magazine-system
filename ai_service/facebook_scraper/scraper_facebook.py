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
import traceback

# Import module config
from config import get_config, reload_config

# Tải cấu hình
config = get_config()

# Tạo thư mục cookies nếu chưa tồn tại
os.makedirs("cookies", exist_ok=True)
# Tạo thư mục logs nếu chưa tồn tại
os.makedirs("logs", exist_ok=True)

# Thiết lập logging
logging.basicConfig(level=logging.INFO, 
                   format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
                   handlers=[
                       logging.FileHandler("logs/facebook_scraper.log", encoding='utf-8'),
                       logging.StreamHandler(sys.stdout)
                   ])
logger = logging.getLogger('facebook_scraper')

# Lấy thông tin Facebook từ config
FB_USERNAME = config.get('FACEBOOK_USERNAME', '')
FB_PASSWORD = config.get('FACEBOOK_PASSWORD', '')
USE_CHROME_PROFILE = config.get('USE_CHROME_PROFILE', False)
CHROME_PROFILE_PATH = config.get('CHROME_PROFILE_PATH', '/app/chrome_profile')
HEADLESS = config.get('HEADLESS', True)
CHROMEDRIVER_PATH = "/usr/local/bin/chromedriver"  # Đường dẫn cố định đến ChromeDriver

# Kiểm tra và tạo thư mục chrome_profile nếu cần
if USE_CHROME_PROFILE and not os.path.exists(CHROME_PROFILE_PATH):
    try:
        os.makedirs(CHROME_PROFILE_PATH, exist_ok=True)
        logger.info(f"Đã tạo thư mục Chrome profile: {CHROME_PROFILE_PATH}")
    except Exception as e:
        logger.error(f"Không thể tạo thư mục Chrome profile: {str(e)}")

def get_db_connection():
    """Get a connection to the Laravel database"""
    # Lấy thông tin kết nối từ config
    db_host = config.get('DB_HOST', 'localhost')
    db_name = config.get('DB_NAME', 'aimagazinedb') 
    db_user = config.get('DB_USER', 'root')
    db_pass = config.get('DB_PASSWORD', '')
    db_port = int(config.get('DB_PORT', 3306))
    
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
    try:
        if platform.system() == 'Windows':
            os.system('taskkill /f /im chrome.exe')
        else:
            os.system('pkill -f chrome')
        logger.info("Đã tắt tất cả các tiến trình Chrome")
        time.sleep(2)  # Cho phép thời gian để Chrome tắt hoàn toàn
    except Exception as e:
        logger.error(f"Lỗi khi tắt Chrome: {str(e)}")

def setup_mysql_connection():
    """Thiết lập kết nối đến MySQL"""
    try:
        # Lấy thông tin kết nối từ config
        db_host = config.get('DB_HOST', 'localhost')
        db_user = config.get('DB_USER', 'root')
        db_password = config.get('DB_PASSWORD', '')
        db_name = config.get('DB_NAME', 'aimagazinedb')
        
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
        # Kiểm tra xem driver có tồn tại không
        if driver is None:
            logger.error("Driver is None, cannot save cookies")
            return False
            
        # Create cookies directory if it doesn't exist
        os.makedirs("cookies", exist_ok=True)
        
        # Save cookies to a file
        cookies = driver.get_cookies()
        if not cookies:
            logger.warning("No cookies to save")
            return False
            
        with open("cookies/facebook_cookies.json", "w") as file:
            json.dump(cookies, file)
            
        logger.info("Successfully saved cookies")
        return True
    except Exception as e:
        logger.error(f"Error saving cookies: {str(e)}")
        return False
        
def load_cookies(driver):
    """Load cookies from file and add them to the browser"""
    try:
        # Kiểm tra xem driver có tồn tại không
        if driver is None:
            logger.error("Driver is None, cannot load cookies")
            return False
            
        cookie_file = "cookies/facebook_cookies.json"
        
        if not os.path.exists(cookie_file):
            logger.warning("Cookie file not found")
            return False
            
        # Load cookies from file
        with open(cookie_file, "r") as file:
            cookies = json.load(file)
            
        if not cookies or not isinstance(cookies, list) or len(cookies) == 0:
            logger.warning("Cookie file is empty or invalid")
            return False
            
        # Navigate to Facebook domain first (required to set cookies)
        driver.get("https://www.facebook.com")
        time.sleep(2)
        
        # Check if cookies are in EditThisCookie format (array of objects with id field)
        if isinstance(cookies, list) and len(cookies) > 0 and 'id' in cookies[0]:
            logger.info("Detected EditThisCookie format")
            # Convert from EditThisCookie format
            for cookie in cookies:
                try:
                    if not cookie or not isinstance(cookie, dict):
                        continue
                        
                    # Remove fields that Selenium doesn't accept
                    cookie_dict = {k: v for k, v in cookie.items() if k in 
                                  ['name', 'value', 'domain', 'path', 'expiry', 'secure', 'httpOnly']}
                    
                    if 'name' not in cookie_dict or 'value' not in cookie_dict:
                        continue
                        
                    driver.add_cookie(cookie_dict)
                except Exception as e:
                    logger.warning(f"Error adding cookie: {str(e)}")
        else:
            # Standard format - just add directly
            for cookie in cookies:
                try:
                    if not cookie or not isinstance(cookie, dict):
                        continue
                        
                    if 'name' not in cookie or 'value' not in cookie:
                        continue
                        
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
        # Kiểm tra xem driver có tồn tại không
        if driver is None:
            logger.error("Driver is None, cannot login to Facebook")
            return False
            
        # First try to login using cookies
        cookie_login_success = False
        logger.info("Attempting login with cookies")
        
        if load_cookies(driver):
            # Navigate to Facebook homepage with cookies loaded
            driver.get("https://www.facebook.com")
            time.sleep(3)
            
            # Take screenshot for debugging
            try:
                screenshot_path = "logs/facebook_cookie_login.png"
                driver.save_screenshot(screenshot_path)
                logger.info(f"Saved login screenshot to {screenshot_path}")
            except Exception as e:
                logger.warning(f"Could not save screenshot: {str(e)}")
            
            # Check if login was successful
            if "facebook.com/login" not in driver.current_url:
                logger.info("Cookie login successful")
                cookie_login_success = True
                return True
            else:
                logger.info("Cookie login failed, attempting login with credentials")
        else:
            logger.info("Cookie login failed, attempting login with credentials")
            
        # If cookie login failed and credentials are provided, try to login with credentials
        if not cookie_login_success:
            # Use provided credentials or fall back to config values
            fb_username = username if username else FB_USERNAME
            fb_password = password if password else FB_PASSWORD
            
            if not fb_username or not fb_password:
                logger.error("No Facebook credentials available. Please provide username/password or configure them in .env")
                return False
                
            # Navigate to Facebook login page
            driver.get("https://www.facebook.com/login")
            time.sleep(3)
            
            # Wait for and fill in username field
            try:
                WebDriverWait(driver, 10).until(
                    EC.presence_of_element_located((By.ID, "email"))
                )
                
                # Fill in username and password
                driver.find_element(By.ID, "email").send_keys(fb_username)
                driver.find_element(By.ID, "pass").send_keys(fb_password)
                
                # Take screenshot before clicking login
                driver.save_screenshot("logs/before_login.png")
                
                # Click login button
                login_button = driver.find_element(By.NAME, "login")
                login_button.click()
                
                # Wait for login to complete
                time.sleep(5)
                
                # Take screenshot to see result
                driver.save_screenshot("logs/after_login.png")
                
                # Check if login successful
                if "login" not in driver.current_url and "checkpoint" not in driver.current_url:
                    logger.info("Login with credentials successful")
                    
                    # Save cookies for future use
                    save_cookies(driver)
                    return True
                else:
                    logger.error("Login with credentials failed, please check username/password")
                    return False
                
            except Exception as e:
                logger.error(f"Error during login process: {str(e)}")
                driver.save_screenshot("logs/login_error.png")
                return False
                
        return cookie_login_success
        
    except Exception as e:
        logger.error(f"Login error: {str(e)}")
        if driver:
            driver.save_screenshot("logs/login_exception.png")
        return False

def setup_driver(headless=None, use_profile=None, chrome_profile="Default"):
    """Setup Chrome driver with options"""
    try:
        # Sử dụng giá trị từ config nếu không được cung cấp
        if headless is None:
            headless = HEADLESS
        if use_profile is None:
            use_profile = USE_CHROME_PROFILE
            
        # Kiểm tra và tắt Chrome nếu đang chạy
        if "linux" in sys.platform:
            try:
                logger.info("Chrome is running. Terminating existing Chrome processes...")
                os.system("pkill -f chrome")
                os.system("pkill -f Chrom")
                time.sleep(2)  # Đợi một chút để Chrome được đóng hoàn toàn
                logger.info("Đã tắt tất cả các tiến trình Chrome")
            except Exception as e:
                logger.error(f"Lỗi khi tắt Chrome: {str(e)}")
        
        # Thiết lập options chung cho Chrome
        options = webdriver.ChromeOptions()
        
        # Đặt đường dẫn đến Chrome binary
        if os.path.exists('/usr/bin/google-chrome'):
            options.binary_location = '/usr/bin/google-chrome'
            
        # Thêm các tùy chọn cho Chrome để tăng tính ổn định
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--disable-gpu')
        options.add_argument('--disable-extensions')
        options.add_argument('--disable-popup-blocking')
        options.add_argument('--ignore-certificate-errors')
        options.add_argument('--remote-debugging-port=9222')
        options.add_argument('--disable-browser-side-navigation')
        options.add_argument('--blink-settings=imagesEnabled=true')
        
        # Đặt user agent phù hợp với Chrome mới nhất
        user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36'
        options.add_argument(f'--user-agent={user_agent}')
        
        # Sử dụng profile Chrome nếu được chỉ định
        if use_profile and chrome_profile:
            if chrome_profile == "Default":
                # Sử dụng đường dẫn mặc định
                chrome_profile_path = CHROME_PROFILE_PATH
            else:
                # Sử dụng đường dẫn được cung cấp
                chrome_profile_path = chrome_profile
                
            logger.info(f"Using Chrome profile at: {chrome_profile_path}")
            options.add_argument(f'--user-data-dir={chrome_profile_path}')
        
        # Chế độ headless
        if headless:
            logger.info("Using headless mode")
            options.add_argument('--headless=new')
            
        # Thêm các tùy chọn cần thiết để tránh phát hiện automation
        options.add_experimental_option('excludeSwitches', ['enable-logging', 'enable-automation'])
        options.add_experimental_option('useAutomationExtension', False)
        
        # Tùy chọn để giải quyết vấn đề không tương thích phiên bản
        options.add_argument('--ignore-certificate-errors')
        options.add_argument('--allow-running-insecure-content')
        options.add_argument('--allow-insecure-localhost')
        
        # Thêm tùy chọn mới để vượt qua kiểm tra phiên bản
        options.add_argument('--disable-blink-features=AutomationControlled')
        
        logger.info(f"Using ChromeDriver at: {CHROMEDRIVER_PATH}")
        
        # Khởi tạo service và driver
        try:
            service = Service(executable_path=CHROMEDRIVER_PATH)
            driver = webdriver.Chrome(service=service, options=options)
            
            # Đặt kích thước cửa sổ trình duyệt
            driver.set_window_size(1920, 1080)
            
            # Đặt thời gian timeout cho việc tải trang
            driver.set_page_load_timeout(30)
            
            logger.info("Chrome WebDriver initialized successfully")
            return driver
        except Exception as e:
            logger.error(f"Error initializing Chrome WebDriver: {str(e)}")
            logger.error(traceback.format_exc())
            
            # Thử cài đặt ChromeDriver tự động nếu khởi tạo thất bại
            try:
                logger.info("Attempting to reinstall ChromeDriver...")
                script_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "install_chromedriver.py")
                subprocess.run([sys.executable, script_path], check=True)
                
                # Thử lại sau khi cài đặt lại
                service = Service(executable_path=CHROMEDRIVER_PATH)
                driver = webdriver.Chrome(service=service, options=options)
                driver.set_window_size(1920, 1080)
                driver.set_page_load_timeout(30)
                
                logger.info("Chrome WebDriver initialized successfully after reinstalling ChromeDriver")
                return driver
            except Exception as e2:
                logger.error(f"Error reinstalling ChromeDriver: {str(e2)}")
                logger.error(traceback.format_exc())
                return None
            
    except Exception as e:
        logger.error(f"Error setting up Chrome driver: {str(e)}")
        logger.error(traceback.format_exc())
        return None

def take_debug_screenshot(driver, element=None, prefix="debug"):
    """Chụp ảnh màn hình hoặc phần tử cụ thể để debug"""
    try:
        # Kiểm tra xem driver có tồn tại không
        if driver is None:
            logger.error("Driver is None, cannot take screenshot")
            return None
            
        # Tạo thư mục logs nếu chưa tồn tại
        os.makedirs("logs", exist_ok=True)
        
        # Tạo tên file với timestamp
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"logs/{prefix}_{timestamp}.png"
        
        if element:
            # Scroll đến phần tử
            driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", element)
            time.sleep(0.5)
            
            # Chụp ảnh trang web
            driver.save_screenshot(filename)
            logger.info(f"Đã chụp ảnh màn hình với phần tử ở giữa: {filename}")
            
            # Highlight phần tử
            driver.execute_script("""
                var element = arguments[0];
                var originalStyle = element.getAttribute('style');
                element.setAttribute('style', originalStyle + '; border: 3px solid red;');
                setTimeout(function() {
                    element.setAttribute('style', originalStyle);
                }, 3000);
            """, element)
        else:
            # Chụp ảnh toàn bộ trang
            driver.save_screenshot(filename)
            logger.info(f"Đã chụp ảnh màn hình: {filename}")
            
        return filename
    except Exception as e:
        logger.warning(f"Lỗi khi chụp ảnh debug: {str(e)}")
        return None

def get_facebook_posts(url, limit=10, headless=True, use_profile=False, chrome_profile="Default", save_to_db=False, username=None, password=None):
    """Get posts from a Facebook page or group"""
    driver = None
    max_attempts = 3
    
    for attempts in range(1, max_attempts + 1):
        try:
            # Thiết lập trình duyệt
            driver = setup_driver(headless, use_profile, chrome_profile)
            
            # Kiểm tra xem driver có được khởi tạo thành công không
            if driver is None:
                logger.error(f"Không thể khởi tạo trình duyệt (lần {attempts}/{max_attempts})")
                time.sleep(2)  # Đợi một chút trước khi thử lại
                continue
            
            # Đăng nhập vào Facebook
            login_success = login_facebook(driver, username, password)
            
            if not login_success:
                logger.warning("Không thể đăng nhập vào Facebook, thử lại...")
                if driver:
                    driver.quit()
                continue
                
            # Xác định xem URL là của nhóm hay trang
            is_group = "group" in url.lower() or "nhom" in url.lower()
            logger.info(f"Truy cập URL {'nhóm' if is_group else 'trang'}: {url}")
            
            # Đảm bảo URL không chứa link tới bài viết cụ thể
            if "/posts/" in url or "/permalink/" in url or "story_fbid" in url:
                logger.info("URL chứa link tới bài viết cụ thể, lấy URL trang/nhóm chính")
                if "facebook.com" in url:
                    if "groups" in url:
                        # Trích xuất URL nhóm
                        group_parts = url.split("facebook.com/groups/")
                        if len(group_parts) > 1:
                            group_id = group_parts[1].split("/")[0]
                            url = f"https://www.facebook.com/groups/{group_id}"
                    else:
                        # Trích xuất URL trang/cá nhân
                        page_parts = url.split("facebook.com/")
                        if len(page_parts) > 1:
                            page_id = page_parts[1].split("/")[0]
                            url = f"https://www.facebook.com/{page_id}"
                logger.info(f"Đã chuyển tới URL trang chính: {url}")
            
            # Truy cập URL Facebook
            driver.get(url)
            
            # Đợi trang tải
            logger.info("Chờ trang tải...")
            time.sleep(8)
            
            # Chụp ảnh màn hình ban đầu để debug
            take_debug_screenshot(driver, None, "initial_page")
            
            # === ĐOẠN CODE ĐƠN GIẢN HÓA THEO MÃ NGƯỜI DÙNG CUNG CẤP ===
            
            # Cuộn trang để tải thêm bài viết - số lần cuộn tỷ lệ với limit
            scroll_count = 0
            max_scrolls = min(limit + 2, 10)  # Giới hạn số lần cuộn tối đa là 10
            
            logger.info(f"Bắt đầu cuộn trang {max_scrolls} lần để tải bài viết...")
            for _ in range(max_scrolls):
                driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
                time.sleep(3)  # Đợi trang tải
                scroll_count += 1
                logger.info(f"Đã cuộn xuống {scroll_count}/{max_scrolls} lần")
            
            # Click vào tất cả các nút "Xem thêm" để mở rộng nội dung bài viết
            logger.info("Bắt đầu click nút 'Xem thêm'...")
            # Sử dụng JavaScript trực tiếp để tìm và click tất cả nút "Xem thêm"
            expand_script = """
                function expandAllSeeMores() {
                    // Danh sách các từ khóa cần tìm
                    const keywords = ['xem thêm', 'see more', 'hiển thị thêm', 'show more'];
                    let clicked = 0;
                    let attempts = 0;
                    
                    // Hàm click được gọi nhiều lần để đảm bảo tất cả nút được click
                    function clickButtons() {
                        // Tất cả các thẻ div có role="button"
                        let buttons = Array.from(document.querySelectorAll('div[role="button"]'));
                        
                        // Thêm span có thể click
                        let spans = Array.from(document.querySelectorAll('span[role="button"]'));
                        buttons = buttons.concat(spans);
                        
                        // Lọc các nút có text chứa từ khóa
                        buttons = buttons.filter(btn => {
                            if (!btn || !btn.textContent) return false;
                            const text = btn.textContent.toLowerCase();
                            return keywords.some(keyword => text.includes(keyword));
                        });
                        
                        // Click từng nút
                        buttons.forEach(btn => {
                            try {
                                    btn.click();
                                clicked++;
                            } catch (e) {
                                // Bỏ qua lỗi
                            }
                        });
                        
                        // Tìm thêm các thẻ span có thể chứa "Xem thêm"
                        let seeMoreSpans = Array.from(document.querySelectorAll('span'))
                            .filter(span => {
                                if (!span || !span.textContent) return false;
                                const text = span.textContent.toLowerCase();
                                return keywords.some(keyword => text.includes(keyword) and text.length < 15);
                            });
                            
                        seeMoreSpans.forEach(span => {
                            try {
                                span.click();
                                clicked++;
                            } catch (e) {
                                // Bỏ qua lỗi
                            }
                        });
                        
                        return clicked;
                    }
                    
                    // Click lần đầu
                    clicked += clickButtons();
                    
                    // Đợi 1 giây rồi click lại để đảm bảo tất cả được mở
                    setTimeout(() => {
                        clicked += clickButtons();
                        console.log("Clicked " + clicked + " 'See more' buttons");
                    }, 1000);
                    
                    return clicked;
                }
                return expandAllSeeMores();
            """
            
            try:
                clicked = driver.execute_script(expand_script)
                logger.info(f"Đã click {clicked} nút 'Xem thêm' bằng JavaScript")
                time.sleep(2)  # Đợi nội dung mở rộng
            except Exception as e:
                logger.warning(f"Lỗi khi click nút 'Xem thêm' bằng JavaScript: {str(e)}")
            
            # Thêm phương pháp truyền thống sử dụng Selenium
            see_more_selectors = [
                "//div[contains(text(), 'Xem thêm') or contains(text(), 'See more')][@role='button']",
                "//span[contains(text(), 'Xem thêm') or contains(text(), 'See more')]",
                "//div[@role='button'][.//span[contains(text(), 'Xem thêm') or contains(text(), 'See more')]]"
            ]
            
            # Thực hiện 2 lần để đảm bảo tất cả nút đều được click
            for attempt in range(2):
                for selector in see_more_selectors:
                    try:
                        see_more_buttons = driver.find_elements(By.XPATH, selector)
                        if see_more_buttons:
                            logger.info(f"Lần {attempt+1}: Tìm thấy {len(see_more_buttons)} nút 'Xem thêm' với selector: {selector}")
                            for btn in see_more_buttons[:50]:  # Tăng số lượng lên 50 nút
                                try:
                                    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", btn)
                                    time.sleep(0.2)
                                    driver.execute_script("arguments[0].click();", btn)
                                    time.sleep(0.2)
                                except Exception as e:
                                    logger.warning(f"Lỗi khi click nút: {str(e)}")
                                    continue
                    except Exception as e:
                        logger.warning(f"Lỗi khi click nút 'Xem thêm' với selector {selector}: {str(e)}")
                        continue
                
                # Đợi một chút trước khi thử lại
                time.sleep(1)
            
            # Chụp ảnh màn hình sau khi đã cuộn và mở rộng bài viết
            take_debug_screenshot(driver, None, "after_expand")
            
            # Trích xuất bài viết theo cách đơn giản
            logger.info("Bắt đầu trích xuất dữ liệu bài viết...")
            posts = []
            
            # Thử các selector chính để tìm bài viết
            post_content_selectors = [
                # Selector trong mã người dùng cung cấp
                "//div[@data-ad-preview='message']",
                # Selector phổ biến khác
                "//div[@role='article']//div[@dir='auto' and @style]",
                "//div[@role='article']//div[@data-ad-comet-preview='message']",
                "//div[@role='feed']//div[@role='article']//div[@dir='auto' and @style]"
            ]
            
            for selector in post_content_selectors:
                try:
                    post_elements = driver.find_elements(By.XPATH, selector)
                    if post_elements:
                        logger.info(f"Tìm thấy {len(post_elements)} phần tử nội dung với selector: {selector}")
                        
                        # Chỉ lấy số lượng bài viết theo limit
                        for idx, element in enumerate(post_elements[:limit]):
                            try:
                                # Trích xuất nội dung
                                content = element.text.strip()
                                
                                # Xử lý nội dung, loại bỏ "Xem thêm"
                                content = clean_content(content)
                                
                                if not content or len(content) < 10:
                                    continue
                                
                                # Tìm URL bài viết gần nhất
                                post_url = ""
                                try:
                                    # Tìm URL bài viết từ phần tử cha
                                    article = element.find_element(By.XPATH, "./ancestor::div[@role='article']")
                                    
                                    # Thử nhiều selector URL khác nhau để đảm bảo tìm được URL
                                    url_selectors = [
                                        # Thời gian đăng thường chứa link đến bài viết
                                        ".//a[contains(@href, '/posts/')]",
                                        ".//a[contains(@href, '/permalink/')]",
                                        ".//a[contains(@href, 'story_fbid=')]",
                                        # Link ảnh/video
                                        ".//a[contains(@href, '/photo.php')]",
                                        ".//a[contains(@href, '/video.php')]",
                                        # Link thời gian đăng
                                        ".//a[contains(@aria-label, '') and contains(@class, 'x1i10hfl')]",
                                        # Link tổng quát có thời gian
                                        ".//a[.//span[contains(text(), 'giờ') or contains(text(), 'phút') or contains(text(), 'hr') or contains(text(), 'min')]]",
                                        # Link bất kỳ trong article
                                        ".//a[contains(@role, 'link')]"
                                    ]
                                    
                                    for url_selector in url_selectors:
                                        url_elements = article.find_elements(By.XPATH, url_selector)
                                        if url_elements:
                                            candidate_url = url_elements[0].get_attribute("href")
                                            if candidate_url:
                                                # Loại bỏ tham số query
                                                post_url = candidate_url.split("?")[0]
                                                logger.info(f"Tìm thấy URL bài viết: {post_url}")
                                                break
                                    
                                    # Nếu không tìm thấy URL, thử tìm ID bài viết từ attribute
                                    if not post_url:
                                        # Thử tìm ID từ các attribute phổ biến
                                        for attr in ["id", "data-id", "data-ft", "data-sigil"]:
                                            attr_value = article.get_attribute(attr)
                                            if attr_value and (":pfbid" in attr_value or "story_fbid" in attr_value):
                                                logger.info(f"Tìm thấy ID bài viết từ attribute {attr}: {attr_value}")
                                                # Tạo URL từ ID nếu có thể
                                                if "groups" in url:
                                                    group_id = url.split("/groups/")[1].split("/")[0] if "/groups/" in url else ""
                                                    post_url = f"https://www.facebook.com/groups/{group_id}/posts/{attr_value}"
                                                else:
                                                    # Thử lấy username/page name từ URL gốc
                                                    page_name = url.split("facebook.com/")[1].split("/")[0] if "facebook.com/" in url else ""
                                                    if page_name and page_name != "groups":
                                                        post_url = f"https://www.facebook.com/{page_name}/posts/{attr_value}"
                                                break
                                except Exception as e:
                                    logger.warning(f"Lỗi khi tìm URL bài viết: {str(e)}")
                                
                                # Nếu vẫn không tìm được URL, sử dụng URL gốc + ID ngẫu nhiên
                                if not post_url:
                                    post_id = str(uuid.uuid4())
                                    post_url = f"{url}#post_{post_id}"
                                    logger.warning(f"Không tìm được URL bài viết, sử dụng ID tạm: {post_id}")
                                
                                # Tìm thông tin tác giả
                                author_name = ""
                                try:
                                    article = element.find_element(By.XPATH, "./ancestor::div[@role='article']")
                                    author_elements = article.find_elements(By.XPATH, ".//h3[contains(@class, 'x1heor9g')]//a | .//strong//a")
                                    if author_elements:
                                        author_name = author_elements[0].text
                                except Exception as e:
                                    logger.warning(f"Lỗi khi tìm thông tin tác giả: {str(e)}")
                                
                                # Tạo ID duy nhất từ URL bài viết
                                post_id = ""
                                if post_url:
                                    # Trích xuất ID từ URL của bài viết
                                    if "/posts/" in post_url:
                                        post_id = post_url.split("/posts/")[1].split("/")[0]
                                    elif "/permalink/" in post_url:
                                        post_id = post_url.split("/permalink/")[1].split("/")[0]
                                    elif "story_fbid=" in post_url:
                                        post_id = post_url.split("story_fbid=")[1].split("&")[0]
                                    elif "/photo.php" in post_url or "/video.php" in post_url:
                                        # Tìm ID từ tham số id trong URL
                                        if "id=" in post_url:
                                            post_id = post_url.split("id=")[1].split("&")[0]
                                    elif "#post_" in post_url:
                                        post_id = post_url.split("#post_")[1]
                                
                                # Nếu không tìm được ID, tạo một ID mới
                                if not post_id:
                                    post_id = str(uuid.uuid4())
                                
                                # Tạo đối tượng bài viết
                                post_data = {
                                    'id': post_id,
                                    'url': post_url,
                                    'content': content,
                                    'author_name': author_name,
                                    'group_id': url.split("/")[-1] if is_group and "groups" in url else "",
                                    'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                                }
                                
                                posts.append(post_data)
                                logger.info(f"Đã thu thập bài viết {idx+1}/{min(len(post_elements), limit)}")
                                
                                # Nếu đã đủ số bài viết theo yêu cầu, dừng lại
                                if len(posts) >= limit:
                                    logger.info(f"Đã đạt đến giới hạn {limit} bài viết, dừng trích xuất")
                                    break
                            except Exception as e:
                                logger.warning(f"Lỗi khi xử lý bài viết {idx}: {str(e)}")
                                continue
                        
                        # Nếu đã tìm được bài viết với selector này, dừng lại
                        if posts:
                            break
                except Exception as e:
                    logger.warning(f"Lỗi khi sử dụng selector {selector}: {str(e)}")
                    continue
            
            # Kiểm tra xem đã tìm thấy bài viết nào chưa
            if not posts:
                logger.warning(f"Không tìm thấy bài viết nào (lần {attempts}/{max_attempts})")
                if driver:
                    driver.quit()
                if attempts < max_attempts:
                    logger.info(f"Thử lại lần {attempts+1}...")
                    time.sleep(5)
                    continue
            else:
                # Lưu vào database nếu được yêu cầu
                if save_to_db and posts:
                    save_posts_to_database(posts)
                return posts
                
        except Exception as e:
            logger.error(f"Lỗi trong quá trình lấy bài viết (lần {attempts}/{max_attempts}): {str(e)}")
            logger.error(traceback.format_exc())
            if attempts < max_attempts:
                logger.info(f"Thử lại lần {attempts+1}...")
                time.sleep(5)
            
        finally:
            if driver:
                driver.quit()
    
    # Nếu đã thử nhiều lần vẫn không thành công
    logger.error(f"Không thể lấy bài viết sau {max_attempts} lần thử")
    return []

def extract_post_content(post_element):
    """Extract post content more accurately, avoiding comments"""
    content = ""
    
    # Danh sách các selector chỉ định cho nội dung bài viết, đã sắp xếp theo thứ tự ưu tiên
    primary_content_selectors = [
        # Selector mới dựa vào developer tools - ưu tiên cao nhất
        './/div[@dir="auto" and @style="text-align: start;"]',
        './/div[@dir="auto" and contains(@style, "text-align")]',
        './/div[@role="article"]//div[@dir="auto" and @style]',
        # Selector từ r2a
        './/div[@id[starts-with(., "r2a")]]//div[@dir="auto"]',
        './/div[@id[contains(., "r2a")]]//div[@dir="auto"]',
        './/div[contains(@id, "r2a")]//div//div//span//div//div',
        # Các selector hiện tại
        './/div[contains(@data-ad-comet-preview, "message")]',
        './/div[@data-ad-preview="message"]',
        './/div[contains(@class, "xdj266r") and not(ancestor::div[contains(@aria-label, "Comment")])]',
        './/div[contains(@class, "x11i5rnm") and not(ancestor::div[contains(@aria-label, "Comment")])]',
        './/div[contains(@class, "userContent")]',
    ]
    
    # Thử các selector chính trước
    for selector in primary_content_selectors:
        try:
            elements = post_element.find_elements(By.XPATH, selector)
            for element in elements:
                # Kiểm tra phần tử không nằm trong comment
                is_comment = element.find_elements(By.XPATH, './ancestor::div[contains(@aria-label, "Comment") or contains(@class, "commentable_item")]')
                if not is_comment:
                    text = element.text.strip()
                    # Loại bỏ text "Xem thêm" khỏi nội dung
                    if "Xem thêm" in text:
                        text = text.replace("Xem thêm", "").strip()
                    if "See more" in text:
                        text = text.replace("See more", "").strip()
                    
                    if text and len(text) > 10:  # Chỉ lấy nội dung đủ dài
                        logger.info(f"Tìm thấy nội dung bài viết với selector: {selector}")
                        return text
            
            # Nếu không tìm thấy nội dung ý nghĩa, thử lấy các phần tử dài hơn 10 ký tự
            for element in elements:
                text = element.text.strip()
                if text and len(text) > 10:
                    return text
        except Exception as e:
            logger.warning(f"Lỗi khi dùng selector {selector}: {str(e)}")
            continue
    
    # Nếu không tìm thấy nội dung bằng selector chính, thử cách tiếp cận khác
    try:
        # Thêm một phương pháp trích xuất dựa trên CSS selector
        try:
            # Thử CSS selector
            content_elements = post_element.find_elements(By.CSS_SELECTOR, 'div[dir="auto"][style*="text-align"]')
            for element in content_elements:
                text = element.text.strip()
                if "Xem thêm" in text:
                    text = text.replace("Xem thêm", "").strip()
                if text and len(text) > 10:
                    return text
        except:
            pass
            
        # Lấy tất cả các div có thuộc tính dir="auto" (thường chứa text nội dung)
        all_text_elements = post_element.find_elements(By.XPATH, './/div[@dir="auto"]')
        
        # Lọc ra các phần tử không thuộc comment section
        for element in all_text_elements:
            # Kiểm tra phần tử không nằm trong comment section
            is_comment = element.find_elements(By.XPATH, './ancestor::div[contains(@aria-label, "Comment") or contains(@class, "comment")]')
            is_timestamp = element.find_elements(By.XPATH, './/*[contains(text(), "giờ") or contains(text(), "phút") or contains(text(), "hr") or contains(text(), "min")]')
            
            if not is_comment and not is_timestamp:
                text = element.text.strip()
                # Lọc các text phổ biến không phải nội dung bài viết
                if text and len(text) > 10 and not any(keyword in text.lower() for keyword in ["like", "comment", "share", "thích", "bình luận", "chia sẻ"]):
                    return text
    except Exception as e:
        logger.warning(f"Lỗi khi trích xuất nội dung bài viết: {str(e)}")
    
    return content

def get_deep_content(driver, post_element):
    """Trích xuất nội dung bài viết với nhiều phương pháp, bao gồm JavaScript"""
    try:
        # Kiểm tra xem driver có tồn tại không
        if driver is None:
            logger.error("Driver is None, cannot extract content")
            return ""
            
        # Method 1: Sử dụng JavaScript để lấy text nội dung
        try:
            content = driver.execute_script("""
                var element = arguments[0];
                // Lọc ra các phần tử text là nội dung chính, không phải comment
                var contentElements = element.querySelectorAll('div[dir="auto"][style*="text-align"]');
                if (contentElements.length > 0) {
                    // Nếu tìm thấy phần tử có style text-align
                    for (var i=0; i < contentElements.length; i++) {
                        var text = contentElements[i].innerText;
                        if (text && text.length > 30) {
                            return text;
                        }
                    }
                }
                
                // Thử phương pháp khác nếu không tìm thấy
                var allTextElements = element.querySelectorAll('div[dir="auto"]');
                var content = '';
                for (var i=0; i < allTextElements.length; i++) {
                    var el = allTextElements[i];
                    // Bỏ qua các phần tử nằm trong comment section
                    var isComment = el.closest('div[aria-label*="Comment"]') != null;
                    if (!isComment) {
                        var text = el.innerText;
                        if (text && text.length > 50) {
                            content = text;
                            break;
                        }
                    }
                }
                return content;
            """, post_element)
            
            if content and len(content) > 20:
                # Loại bỏ "Xem thêm" nếu có
                if "Xem thêm" in content:
                    content = content.replace("Xem thêm", "").strip()
                if "See more" in content:
                    content = content.replace("See more", "").strip()
                return content
        except Exception as e:
            logger.warning(f"Lỗi khi trích xuất nội dung bằng JavaScript: {str(e)}")
        
        # Method 2: Trích xuất từ CSS selector cụ thể theo mẫu bạn cung cấp
        try:
            specific_elements = post_element.find_elements(By.CSS_SELECTOR, 'div[dir="auto"][style*="text-align: start"]')
            for element in specific_elements:
                text = element.text
                if text and len(text) > 20:
                    # Loại bỏ "Xem thêm" nếu có
                    if "Xem thêm" in text:
                        text = text.replace("Xem thêm", "").strip()
                    if "See more" in text:
                        text = text.replace("See more", "").strip()
                    return text
        except Exception as e:
            logger.warning(f"Lỗi khi trích xuất nội dung bằng CSS selector cụ thể: {str(e)}")
        
        # Method 3: Duyệt qua tất cả các phần tử và phân tích nội dung
        try:
            # Ưu tiên các phần tử có thuộc tính dir="auto" và style
            all_text_elements = post_element.find_elements(By.XPATH, './/div[@dir="auto" and @style]')
            all_texts = []
            
            for element in all_text_elements:
                # Loại bỏ phần tử nằm trong comment
                is_comment = element.find_elements(By.XPATH, './ancestor::div[contains(@aria-label, "Comment")]')
                if not is_comment:
                    text = element.text.strip()
                    if text and len(text) > 20:
                        all_texts.append(text)
            
            # Chọn đoạn text dài nhất nếu có nhiều đoạn
            if all_texts:
                longest_text = max(all_texts, key=len)
                # Loại bỏ "Xem thêm" nếu có
                if "Xem thêm" in longest_text:
                    longest_text = longest_text.replace("Xem thêm", "").strip()
                if "See more" in longest_text:
                    longest_text = longest_text.replace("See more", "").strip()
                return longest_text
        except Exception as e:
            logger.warning(f"Lỗi khi phân tích tất cả các phần tử text: {str(e)}")
            
    except Exception as e:
        logger.error(f"Lỗi trong hàm get_deep_content: {str(e)}")
    
    return ""

def find_post_content_by_attribute(driver, post_element):
    """
    Tìm nội dung bài viết dựa vào thuộc tính r2a mà bạn đã chỉ ra
    """
    try:
        # Kiểm tra xem driver có tồn tại không
        if driver is None:
            logger.error("Driver is None, cannot find post content by attribute")
            return ""
            
        # Thử trực tiếp với CSS selector theo ví dụ bạn đã cung cấp
        script = """
            return (function() {
                var post = arguments[0];
                
                // Tìm tất cả các phần tử div có ID bắt đầu bằng r2a
                var r2aElements = [];
                var allDivs = post.getElementsByTagName('div');
                for (var i = 0; i < allDivs.length; i++) {
                    var div = allDivs[i];
                    if (div.id && div.id.startsWith('r2a')) {
                        r2aElements.push(div);
                    }
                }
                
                // Tìm nội dung trong các phần tử r2a
                if (r2aElements.length > 0) {
                    for (var i = 0; i < r2aElements.length; i++) {
                        var contentDivs = r2aElements[i].querySelectorAll('div[dir="auto"][style*="text-align"]');
                        if (contentDivs.length > 0) {
                            // Lấy div nội dung đầu tiên có text
                            for (var j = 0; j < contentDivs.length; j++) {
                                var text = contentDivs[j].innerText;
                                // Loại bỏ "Xem thêm" nếu có
                                text = text.replace("Xem thêm", "").replace("See more", "").trim();
                                if (text && text.length > 20) {
                                    return text;
                                }
                            }
                        }
                        
                        // Nếu không tìm thấy theo cách trên, lấy tất cả các div có dir="auto"
                        var autoDivs = r2aElements[i].querySelectorAll('div[dir="auto"]');
                        for (var j = 0; j < autoDivs.length; j++) {
                            var text = autoDivs[j].innerText;
                            // Loại bỏ "Xem thêm" nếu có
                            text = text.replace("Xem thêm", "").replace("See more", "").trim();
                            if (text && text.length > 30) {  // Chỉ lấy nội dung đủ dài
                                return text;
                            }
                        }
                    }
                }
                
                // Thử tìm theo cấu trúc cụ thể bạn đã cung cấp: "#r2a > div > div > span > div > div"
                var elementsWithStyle = post.querySelectorAll('div[style*="text-align"]');
                for (var i = 0; i < elementsWithStyle.length; i++) {
                    var text = elementsWithStyle[i].innerText;
                    text = text.replace("Xem thêm", "").replace("See more", "").trim();
                    if (text && text.length > 20) {
                        return text;
                    }
                }
                
                return "";
            })();
        """
        
        content = driver.execute_script(script, post_element)
        if content and len(content) > 20:
            return content
            
        # Nếu không tìm được bằng JavaScript, thử với XPath
        r2a_elements = post_element.find_elements(By.XPATH, './/div[starts-with(@id, "r2a")]')
        if r2a_elements:
            for r2a in r2a_elements:
                # Tìm trong r2a theo cấu trúc bạn đã cung cấp
                content_divs = r2a.find_elements(By.XPATH, './/div/div/span/div/div')
                for div in content_divs:
                    text = div.text.strip()
                    if text and len(text) > 20:
                        # Loại bỏ "Xem thêm" nếu có
                        text = text.replace("Xem thêm", "").replace("See more", "").strip()
                        return text
        
    except Exception as e:
        logger.warning(f"Lỗi khi tìm nội dung theo thuộc tính r2a: {str(e)}")
    
    return ""

def extract_posts_data(driver, is_group=False, url="", limit=10):
    """Extract post data from page"""
    # Kiểm tra xem driver có tồn tại không
    if driver is None:
        logger.error("Driver is None, cannot extract posts data")
        return []
        
    posts = []
    selectors_tried = 0
    max_selectors = 5
    
    # Danh sách các XPath selector để thử - Cập nhật các selector mới cho Facebook UI hiện tại
    post_selectors = [
        # Selector chung cho bài viết - phiên bản mới nhất
        '//div[@role="feed"]/div[contains(@class, "x1lliihq")]//div[@role="article"]',
        # Selector bài viết trong feed
        '//div[contains(@role, "feed")]//div[@role="article"]',
        # Selector cho bài viết tường nhà/trang
        '//div[@data-pagelet="FeedUnit"]//div[@role="article"]',
        # Selector cho bài viết trong nhóm - phiên bản mới nhất
        '//div[contains(@role, "feed")]//div[contains(@class, "x1yztbdb")]//div[@role="article"]',
        # Fallback 
        '//div[@role="article"]'
    ]
    
    logger.info("Bắt đầu tìm bài viết với nhiều selector khác nhau...")
    
    # Lấy URL hiện tại để kiểm tra
    current_url = driver.current_url
    logger.info(f"URL hiện tại khi bắt đầu trích xuất: {current_url}")
    
    # Kiểm tra nếu chúng ta đã được điều hướng vào một trang bài viết cụ thể
    if "/posts/" in current_url or "/permalink/" in current_url or "story_fbid" in current_url:
        logger.warning("Đã bị điều hướng vào trang bài viết cụ thể. Quay lại trang chính...")
        # Quay lại URL ban đầu
        original_url = current_url.split("/posts/")[0] if "/posts/" in current_url else current_url.split("/permalink/")[0]
        driver.get(original_url)
        time.sleep(5)  # Đợi trang tải
    
    # Thêm đoạn code debug để chụp lại trang hiện tại
    try:
        take_debug_screenshot(driver, None, "before_extract_posts")
    except Exception as e:
        logger.warning(f"Không thể chụp ảnh debug: {str(e)}")
    
    # Thử nhiều selector khác nhau
    for selector in post_selectors:
        selectors_tried += 1
        try:
            logger.info(f"Thử selector: {selector}")
            post_elements = driver.find_elements(By.XPATH, selector)
            
            if post_elements:
                logger.info(f"Tìm thấy {len(post_elements)} bài viết với selector: {selector}")
                
                # Thêm kiểm tra để đảm bảo chúng ta đang ở trang feed chính
                if not post_elements[0].is_displayed():
                    logger.warning("Phần tử bài viết đầu tiên không hiển thị, có thể đã bị điều hướng")
                    continue
                
                # Trích xuất dữ liệu từ các bài viết
                for idx, post in enumerate(post_elements[:50]):  # Giới hạn 50 bài viết
                    try:
                        # QUAN TRỌNG: Kiểm tra xem chúng ta có bị điều hướng không
                        if "/posts/" in driver.current_url or "/permalink/" in driver.current_url or "story_fbid" in driver.current_url:
                            logger.warning(f"Phát hiện điều hướng sang bài viết cụ thể: {driver.current_url}")
                            # Quay lại trang chính
                            original_url = driver.current_url.split("/posts/")[0] if "/posts/" in driver.current_url else driver.current_url.split("/permalink/")[0]
                            driver.get(original_url)
                            time.sleep(5)  # Đợi trang tải
                            # Tìm lại các bài viết
                            post_elements = driver.find_elements(By.XPATH, selector)
                            if idx < len(post_elements):
                                post = post_elements[idx]
                            else:
                                continue
                        
                        # Cuộn đến bài viết để đảm bảo hiển thị - KHÔNG click vào bài viết
                        try:
                            driver.execute_script("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", post)
                            time.sleep(0.5)  # Đợi ngắn sau khi cuộn
                        except Exception as e:
                            logger.warning(f"Lỗi khi cuộn đến bài viết: {str(e)}")
                        
                        # Chụp ảnh debug cho bài viết hiện tại
                        try:
                            take_debug_screenshot(driver, post, f"post_{idx}")
                        except Exception as e:
                            logger.warning(f"Không thể chụp ảnh bài viết {idx}: {str(e)}")
                        
                        # KHÔNG CLICK vào bài viết - chỉ trích xuất thông tin ngay tại trang feed
                        # Trích xuất URL bài viết mà không nhấp vào nó
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
                        
                        # Phương pháp 1: Tìm theo thuộc tính r2a
                        r2a_content = find_post_content_by_attribute(driver, post)
                        if r2a_content:
                            content = r2a_content
                            logger.info(f"Đã lấy được nội dung bài viết {idx} với phương pháp r2a")
                        
                        # Phương pháp 2: Nếu không tìm được, sử dụng phương pháp cũ
                        if not content or len(content) < 20:
                            try:
                                temp_content = extract_post_content(post)
                                if temp_content and len(temp_content) > len(content):
                                    content = temp_content
                                    logger.info(f"Đã lấy được nội dung bài viết {idx} với phương pháp thông thường")
                            except Exception as extract_error:
                                logger.warning(f"Lỗi khi trích xuất nội dung bài viết {idx}: {str(extract_error)}")
                        
                        # Phương pháp 3: Nếu vẫn không tìm được, sử dụng phương pháp sâu
                        if not content or len(content) < 20:
                            try:
                                logger.info(f"Thử phương pháp trích xuất nội dung sâu hơn cho bài viết {idx}...")
                                deep_content = get_deep_content(driver, post)
                                if deep_content and len(deep_content) > len(content):
                                    content = deep_content
                                    logger.info(f"Đã lấy được nội dung bài viết {idx} với phương pháp sâu")
                            except Exception as e:
                                logger.warning(f"Lỗi khi trích xuất nội dung sâu bài viết {idx}: {str(e)}")
                        
                        # Thêm đoạn code debug sau phần trích xuất nội dung
                        # Chụp ảnh debug nếu không tìm thấy nội dung
                        if not content or len(content) < 20:
                            logger.warning(f"Không thể trích xuất nội dung đầy đủ bài viết {idx}, chụp ảnh debug")
                            take_debug_screenshot(driver, post, f"post_no_content_{idx}")
                        
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
                            # Tạo ID duy nhất từ URL bài viết
                            post_id = ""
                            if post_url:
                                # Trích xuất ID từ URL của bài viết
                                if "/posts/" in post_url:
                                    post_id = post_url.split("/posts/")[1].split("/")[0]
                                elif "/permalink/" in post_url:
                                    post_id = post_url.split("/permalink/")[1].split("/")[0]
                                elif "story_fbid=" in post_url:
                                    post_id = post_url.split("story_fbid=")[1].split("&")[0]
                                elif "/photo.php" in post_url or "/video.php" in post_url:
                                    # Tìm ID từ tham số id trong URL
                                    if "id=" in post_url:
                                        post_id = post_url.split("id=")[1].split("&")[0]
                                elif "#post_" in post_url:
                                    post_id = post_url.split("#post_")[1]
                            
                            # Nếu không tìm được ID, tạo một ID mới
                            if not post_id:
                                post_id = str(uuid.uuid4())
                            
                            # Thu thập thông tin tác giả
                            author_name = ""
                            author_url = ""
                            
                            # Tìm tên tác giả
                            author_selectors = [
                                './/a[contains(@href, "/profile.php") or contains(@href, "/user/") or contains(@href, "/people/")]',
                                './/h3[contains(@class, "x1heor9g")]//a',
                                './/strong//a',
                                './/span[contains(@class, "x3nfvp2")]//a',
                                './/a[contains(@role, "link") and contains(@tabindex, "0")]'
                            ]
                            
                            for author_selector in author_selectors:
                                try:
                                    author_elements = post.find_elements(By.XPATH, author_selector)
                                    if author_elements:
                                        author_name = author_elements[0].text
                                        author_url = author_elements[0].get_attribute("href")
                                        if author_name:
                                            break
                                except Exception as e:
                                    logger.warning(f"Lỗi khi tìm tác giả với selector {author_selector}: {str(e)}")
                                    continue
                                
                            # Tạo và thêm dữ liệu bài viết
                            post_data = {
                                'id': post_id,
                                'url': post_url,
                                'content': content.strip() if content else "",
                                'post_time': post_time,
                                'author_name': author_name,
                                'author_url': author_url,
                                'group_id': url.split("/")[-1] if is_group and "groups" in url else "",
                                'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                            }
                            
                            posts.append(post_data)
                            logger.info(f"Đã thu thập bài viết {idx+1}/{len(post_elements[:50])}")
                            
                            # Kiểm tra nếu đã đủ số lượng bài viết yêu cầu
                            if len(posts) >= limit:
                                logger.info(f"Đã đạt đến giới hạn {limit} bài viết, dừng trích xuất")
                                break
                    except Exception as e:
                        logger.error(f"Lỗi khi xử lý bài viết {idx}: {str(e)}")
                        logger.error(traceback.format_exc())
                
                # Nếu đã thu thập được bài viết, kết thúc vòng lặp
                if posts:
                    break
            else:
                logger.warning(f"Không tìm thấy bài viết nào với selector: {selector}")
                
        except Exception as e:
            logger.error(f"Lỗi khi sử dụng selector {selector}: {str(e)}")
            logger.error(traceback.format_exc())
        
        # Kiểm tra nếu đã thử hết các selector hoặc đã tìm thấy bài viết
        if selectors_tried >= max_selectors or posts:
            break
    
    # Log kết quả
    if posts:
        logger.info(f"Trích xuất thành công {len(posts)} bài viết")
    else:
        logger.warning("Không trích xuất được bài viết nào")
    
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
        
        # Kiểm tra kết nối database
        if not connection.is_connected():
            logger.error("Không thể kết nối đến cơ sở dữ liệu")
            return
            
        # Kiểm tra cấu trúc bảng trước khi chèn dữ liệu
        try:
            cursor.execute("DESCRIBE facebook_posts")
            columns = [column[0] for column in cursor.fetchall()]
            logger.info(f"Cấu trúc bảng facebook_posts: {', '.join(columns)}")
        except Exception as e:
            logger.error(f"Lỗi khi kiểm tra cấu trúc bảng: {str(e)}")
            if 'connection' in locals() and connection.is_connected():
                cursor.close()
                connection.close()
            return
        
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
        
        # Giới hạn số lượng bài viết để tránh timeout
        max_posts = min(len(posts), 20)  # Tối đa 20 bài viết mỗi lần
        logger.info(f"Xử lý {max_posts}/{len(posts)} bài viết để tránh timeout")
        
        for post in posts[:max_posts]:
            try:
                content = post.get("content", "")
                url = post.get("url", "")
                
                # Skip empty content
                if not content or len(content.strip()) < 10:
                    logger.warning(f"Bỏ qua bài viết có nội dung trống hoặc quá ngắn: '{content}'")
                    continue
                
                # Giới hạn kích thước nội dung để tránh lỗi間に  # Loại bỏ tham số query
                if len(content) > 10000:  # Giới hạn 10K ký tự
                    logger.warning(f"Cắt nội dung bài viết dài từ {len(content)} xuống 10000 ký tự")
                    content = content[:9995] + "..."
                
                # Log dữ liệu trước khi chèn (giúp debug)
                logger.info(f"Lưu bài viết: URL={url[:30] + '...' if len(url) > 30 else url}")
                logger.info(f"Nội dung: {content[:100] + '...' if len(content) > 100 else content}")
                
                values = (
                    content,
                    url,  # Map to source_url field
                    post.get("page_or_group_name", ""),
                    0,  # processed = false
                    now,  # created_at
                    now   # updated_at
                )
                
                cursor.execute(insert_query, values)
                success_count += 1
                
                # Commit sau mỗi 5 bài viết để tránh transaction quá lớn
                if success_count % 5 == 0:
                    connection.commit()
                    logger.info(f"Đã commit {success_count} bài viết")
                
            except Exception as e:
                logger.error(f"Lỗi khi chèn bài viết vào cơ sở dữ liệu: {str(e)}")
                continue
        
        # Commit the transaction
        connection.commit()
        logger.info(f"Đã lưu {success_count}/{len(posts)} bài viết vào cơ sở dữ liệu")
        
    except Exception as e:
        logger.error(f"Lỗi khi lưu bài viết vào cơ sở dữ liệu: {str(e)}")
        logger.error(traceback.format_exc())  # Thêm stack trace để debug
    finally:
        if 'connection' in locals() and connection.is_connected():
            cursor.close()
            connection.close()
            logger.info("Đã đóng kết nối đến cơ sở dữ liệu")

def main():
    """
    Hàm chính để chạy script từ command line
    """
    parser = argparse.ArgumentParser(description='Scrape posts from a Facebook page')
    parser.add_argument('url', help='The URL of the Facebook page to scrape')
    parser.add_argument('--limit', type=int, default=config.get('DEFAULT_POST_LIMIT', 10), 
                      help='Number of posts to scrape')
    parser.add_argument('--headless', action='store_true', default=config.get('HEADLESS', True),
                      help='Run browser in headless mode')
    parser.add_argument('--use-profile', action='store_true', default=config.get('USE_CHROME_PROFILE', False),
                      help='Use Chrome profile')
    parser.add_argument('--save-to-db', action='store_true', 
                      help='Save scraped posts to the database')
    parser.add_argument('--username', type=str, default=None,
                      help='Facebook username or email')
    parser.add_argument('--password', type=str, default=None,
                      help='Facebook password')
    
    args = parser.parse_args()
    
    # Ghi đè lên cấu hình từ tham số dòng lệnh
    username = args.username if args.username else config.get('FACEBOOK_USERNAME', '')
    password = args.password if args.password else config.get('FACEBOOK_PASSWORD', '')
    
    try:
        # Scrape posts from the provided URL
        posts = get_facebook_posts(
            url=args.url,
            limit=args.limit,
            headless=args.headless,
            use_profile=args.use_profile,
            save_to_db=args.save_to_db,
            username=username,
            password=password
        )
        
        # Print out a brief summary of the scraped posts
        if posts:
            print(f"\nScraped {len(posts)} posts:")
            for i, post in enumerate(posts, 1):
                print(f"{i}. Post type: {post.get('type', 'unknown')}, Author: {post.get('author_name', 'Unknown')}")
                print(f"   Content: {post.get('content', '')[:100]}...")
                print(f"   URL: {post.get('url', 'N/A')}")
                print(f"   Reactions: {post.get('reaction_count', 0)}, Comments: {post.get('comment_count', 0)}, Shares: {post.get('share_count', 0)}")
                print()
        else:
            print("No posts were scraped.")
    
    except Exception as e:
        print(f"Error: {str(e)}")
        traceback.print_exc()
        return 1
        
    return 0

if __name__ == "__main__":
    main()

# Thêm phương thức mới để xử lý nội dung
def clean_content(content):
    """Làm sạch nội dung bài viết, loại bỏ các cụm từ không mong muốn"""
    if not content:
        return ""
        
    # Danh sách các cụm từ cần loại bỏ
    phrases_to_remove = [
        "Xem thêm",
        "See more",
        "Hiển thị thêm",
        "Show more",
        "Xem nội dung đầy đủ",
        "View full content",
        "… Xem thêm",
        "... Xem thêm",
        "...Xem thêm",
        "… See more",
        "... See more",
        "...See more"
    ]
    
    # Loại bỏ từng cụm từ
    clean_text = content
    for phrase in phrases_to_remove:
        # Loại bỏ cụm từ ở cuối nội dung
        if clean_text.endswith(phrase):
            clean_text = clean_text[:-len(phrase)].strip()
        
        # Loại bỏ cụm từ ở bất kỳ vị trí nào trong nội dung
        clean_text = clean_text.replace(phrase, "").strip()
    
    # Loại bỏ nhiều dấu cách liên tiếp
    clean_text = " ".join(clean_text.split())
    
    return clean_text

# Thêm clean_content như một phương thức tĩnh
get_facebook_posts.clean_content = clean_content
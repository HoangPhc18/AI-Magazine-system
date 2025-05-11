#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Quản lý cấu hình cho scraper service từ file .env
Đọc các biến từ file .env và cung cấp truy cập thống nhất
"""

import os
import sys
import logging
from pathlib import Path
from dotenv import load_dotenv
import json
import requests

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.StreamHandler()
    ]
)
logger = logging.getLogger("config")

# Đường dẫn đến các file .env
ROOT_DIR = Path(__file__).resolve().parents[2]  # Đường dẫn đến thư mục gốc
AI_SERVICE_DIR = Path(__file__).resolve().parents[1]  # Đường dẫn đến thư mục ai_service
SCRAPER_DIR = Path(__file__).resolve().parent  # Đường dẫn đến thư mục scraper

# Đường dẫn đến các file .env
ROOT_ENV_FILE = ROOT_DIR / ".env"
AI_SERVICE_ENV_FILE = AI_SERVICE_DIR / ".env"
SCRAPER_ENV_FILE = SCRAPER_DIR / ".env"

# Biến để lưu trữ cấu hình
config = {}

def load_config():
    """
    Tải cấu hình từ các file .env theo thứ tự ưu tiên:
    1. .env trong thư mục scraper (nếu có)
    2. .env trong thư mục ai_service
    3. .env trong thư mục gốc
    """
    global config
    
    # Tải file .env từ thư mục gốc trước (ưu tiên thấp nhất)
    if ROOT_ENV_FILE.exists():
        logger.info(f"Tải cấu hình từ file {ROOT_ENV_FILE}")
        load_dotenv(dotenv_path=ROOT_ENV_FILE, override=True)
    
    # Tải file .env từ thư mục ai_service (ưu tiên trung bình)
    if AI_SERVICE_ENV_FILE.exists():
        logger.info(f"Tải cấu hình từ file {AI_SERVICE_ENV_FILE}")
        load_dotenv(dotenv_path=AI_SERVICE_ENV_FILE, override=True)
    
    # Tải file .env từ thư mục scraper nếu có (ưu tiên cao nhất)
    if SCRAPER_ENV_FILE.exists():
        logger.info(f"Tải cấu hình từ file {SCRAPER_ENV_FILE}")
        load_dotenv(dotenv_path=SCRAPER_ENV_FILE, override=True)
    
    # Đọc các biến môi trường vào dict config
    # Database Configuration
    config["DB_HOST"] = os.getenv("DB_HOST", "localhost")
    config["DB_USER"] = os.getenv("DB_USERNAME", "tap_chi_dien_tu")
    config["DB_PASSWORD"] = os.getenv("DB_PASSWORD", "Nh[Xg3KT06)FI91X")
    config["DB_NAME"] = os.getenv("DB_DATABASE", "tap_chi_dien_tu")
    config["DB_PORT"] = int(os.getenv("DB_PORT", "3306"))
    config["DB_CONNECTION"] = os.getenv("DB_CONNECTION", "mysql")
    
    # API Configuration
    config["BACKEND_URL"] = os.getenv("BACKEND_URL", "http://localhost")
    config["BACKEND_PORT"] = os.getenv("BACKEND_PORT", "80")
    config["SCRAPER_URL"] = os.getenv("SCRAPER_URL", "http://localhost:55025/scraper")
    
    # Kiểm tra xem chúng ta có đang chạy trong Docker container không
    if os.path.exists('/.dockerenv'):
        logger.info("Phát hiện đang chạy trong Docker container.")
        # Phát hiện hệ điều hành hoặc môi trường
        is_linux = False
        
        # Kiểm tra nếu đang chạy trên Linux
        if os.path.exists('/etc/os-release'):
            with open('/etc/os-release', 'r') as f:
                if 'Linux' in f.read():
                    is_linux = True
        
        # Hoặc kiểm tra theo platform
        if 'linux' in sys.platform.lower():
            is_linux = True
            
        if is_linux and config["BACKEND_URL"] == "http://localhost":
            logger.info("Phát hiện Linux. Điều chỉnh BACKEND_URL...")
            config["BACKEND_URL"] = "http://172.17.0.1"
            config["DB_HOST"] = "172.17.0.1"
        elif config["BACKEND_URL"] == "http://localhost":
            logger.info("Phát hiện Windows. Điều chỉnh BACKEND_URL...")
            config["BACKEND_URL"] = "http://host.docker.internal"
            config["DB_HOST"] = "host.docker.internal"
        
        logger.info(f"BACKEND_URL đã điều chỉnh thành: {config['BACKEND_URL']}")
        logger.info(f"DB_HOST đã điều chỉnh thành: {config['DB_HOST']}")
    
    # Service Configuration
    config["PORT_SCRAPER"] = int(os.getenv("PORT_SCRAPER", "5001"))
    config["HOST"] = os.getenv("HOST", "0.0.0.0")
    config["DEBUG"] = os.getenv("DEBUG", "False").lower() == "true"
    
    # API URLs
    # Xác định BASE_API_URL dựa trên BACKEND_URL và BACKEND_PORT
    if config["BACKEND_PORT"] == "80":
        config["BASE_API_URL"] = f"{config['BACKEND_URL']}/api"
    else:
        config["BASE_API_URL"] = f"{config['BACKEND_URL']}:{config['BACKEND_PORT']}/api"
    
    # Kiểm tra nếu ta cần sử dụng domain magazine.test thay vì IP
    if os.path.exists('/.dockerenv'):
        # Khi chạy trong Docker, kiểm tra xem có thể sử dụng domain magazine.test
        try:
            # Tạo URL kiểm tra
            test_url = f"{config['BACKEND_URL']}/api/categories"
            logger.info(f"Thử kết nối đến API bằng URL: {test_url}")
            
            # Thiết lập header Host
            headers = {'Host': 'magazine.test'}
            
            response = requests.get(test_url, headers=headers, timeout=5)
            if response.status_code == 200:
                logger.info("Kết nối thành công đến backend thông qua IP với Host header magazine.test")
                # Đã kết nối được, sử dụng URL cùng với header Host
                config["USE_HOST_HEADER"] = True
            else:
                logger.warning(f"Kết nối đến backend thông qua IP với Host header magazine.test trả về mã lỗi: {response.status_code}")
                config["USE_HOST_HEADER"] = False
        except Exception as e:
            logger.warning(f"Không thể kết nối đến backend thông qua IP với Host header: {str(e)}")
            config["USE_HOST_HEADER"] = False
            
            # Thử kết nối trực tiếp đến magazine.test nếu gặp lỗi với IP
            try:
                # Thử kết nối trực tiếp đến magazine.test
                test_url_domain = "http://magazine.test/api/categories"
                logger.info(f"Thử kết nối trực tiếp đến domain: {test_url_domain}")
                response = requests.get(test_url_domain, timeout=5)
                
                if response.status_code == 200:
                    logger.info("Kết nối thành công đến backend thông qua domain magazine.test")
                    # Cập nhật URL sử dụng domain
                    config["BACKEND_URL"] = "http://magazine.test"
                    config["BASE_API_URL"] = f"{config['BACKEND_URL']}/api"
                    config["USE_HOST_HEADER"] = False
                else:
                    logger.warning(f"Kết nối đến domain magazine.test trả về mã lỗi: {response.status_code}")
            except Exception as domain_err:
                logger.warning(f"Không thể kết nối trực tiếp đến domain magazine.test: {str(domain_err)}")
    else:
        config["USE_HOST_HEADER"] = False
    
    config["CATEGORIES_API_URL"] = f"{config['BASE_API_URL']}/categories"
    config["SUBCATEGORIES_API_URL"] = f"{config['BASE_API_URL']}/subcategories"
    config["ARTICLES_API_URL"] = f"{config['BASE_API_URL']}/articles"
    config["ARTICLES_BATCH_API_URL"] = f"{config['BASE_API_URL']}/articles/batch"
    config["ARTICLES_IMPORT_API_URL"] = f"{config['BASE_API_URL']}/articles/import"
    config["ARTICLES_CHECK_API_URL"] = f"{config['BASE_API_URL']}/articles/check"
    
    # Scraper configuration
    config["MAX_ARTICLES_PER_CATEGORY"] = int(os.getenv("MAX_ARTICLES_PER_CATEGORY", "3"))
    config["MAX_ARTICLES_PER_SUBCATEGORY"] = int(os.getenv("MAX_ARTICLES_PER_SUBCATEGORY", "2"))
    config["RETENTION_DAYS"] = int(os.getenv("RETENTION_DAYS", "7"))
    config["DEFAULT_BATCH_SIZE"] = int(os.getenv("DEFAULT_BATCH_SIZE", "5"))
    config["USE_SUBCATEGORIES"] = os.getenv("USE_SUBCATEGORIES", "True").lower() == "true"
    
    # External API keys
    config["WORLDNEWS_API_KEY"] = os.getenv("WORLDNEWS_API_KEY", "")
    config["CURRENTS_API_KEY"] = os.getenv("CURRENTS_API_KEY", "")
    
    # AI Configuration
    config["GEMINI_API_KEY"] = os.getenv("GEMINI_API_KEY", "")
    
    # Log các cấu hình đã tải
    logger.info("Đã tải cấu hình thành công")
    return config

def get_config(key=None, default=None):
    """
    Lấy giá trị cấu hình theo key
    
    Args:
        key: Tên biến cấu hình cần lấy
        default: Giá trị mặc định nếu không tìm thấy
        
    Returns:
        Giá trị của biến cấu hình hoặc giá trị mặc định
    """
    if not config:
        load_config()
    
    if key is None:
        return config
    
    return config.get(key, default)

def reload_config():
    """
    Tải lại cấu hình từ file .env
    """
    load_config()
    return config

# Tải cấu hình khi import module
load_config()

if __name__ == "__main__":
    # Hiển thị cấu hình khi chạy trực tiếp
    print(json.dumps(config, indent=4)) 
#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Quản lý cấu hình cho facebook_scraper service từ file .env
Đọc các biến từ file .env và cung cấp truy cập thống nhất
"""

import os
import sys
import logging
from pathlib import Path
from dotenv import load_dotenv
import json

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
FACEBOOK_SCRAPER_DIR = Path(__file__).resolve().parent  # Đường dẫn đến thư mục facebook_scraper

# Đường dẫn đến các file .env
ROOT_ENV_FILE = ROOT_DIR / ".env"
AI_SERVICE_ENV_FILE = AI_SERVICE_DIR / ".env"
FACEBOOK_SCRAPER_ENV_FILE = FACEBOOK_SCRAPER_DIR / ".env"

# Biến để lưu trữ cấu hình
config = {}

def load_config():
    """
    Tải cấu hình từ các file .env theo thứ tự ưu tiên:
    1. .env trong thư mục facebook_scraper (nếu có)
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
    
    # Tải file .env từ thư mục facebook_scraper nếu có (ưu tiên cao nhất)
    if FACEBOOK_SCRAPER_ENV_FILE.exists():
        logger.info(f"Tải cấu hình từ file {FACEBOOK_SCRAPER_ENV_FILE}")
        load_dotenv(dotenv_path=FACEBOOK_SCRAPER_ENV_FILE, override=True)
    
    # Đọc các biến môi trường vào dict config
    # Database Configuration
    config["DB_HOST"] = os.getenv("DB_HOST", "localhost")
    config["DB_USER"] = os.getenv("DB_USERNAME", "root")
    config["DB_PASSWORD"] = os.getenv("DB_PASSWORD", "")
    config["DB_NAME"] = os.getenv("DB_DATABASE", "aimagazinedb")
    config["DB_PORT"] = int(os.getenv("DB_PORT", "3306"))
    config["DB_CONNECTION"] = os.getenv("DB_CONNECTION", "mysql")
    config["DATABASE_URL"] = os.getenv("DATABASE_URL", "mysql://root:@localhost:3306/magazine")
    
    # API Configuration
    config["BACKEND_URL"] = os.getenv("BACKEND_URL", "http://localhost")
    config["BACKEND_PORT"] = os.getenv("BACKEND_PORT", "8000")
    config["AI_SERVICE_URL"] = os.getenv("AI_SERVICE_URL", "http://localhost:55025")
    
    # Service Configuration
    config["PORT_FACEBOOK_SCRAPER"] = int(os.getenv("PORT_FACEBOOK_SCRAPER", "5004"))
    config["HOST"] = os.getenv("HOST", "0.0.0.0")
    config["DEBUG"] = os.getenv("DEBUG", "False").lower() == "true"
    
    # Facebook Credentials
    config["FACEBOOK_USERNAME"] = os.getenv("FACEBOOK_USERNAME", "")
    config["FACEBOOK_PASSWORD"] = os.getenv("FACEBOOK_PASSWORD", "")
    config["USE_CHROME_PROFILE"] = os.getenv("USE_CHROME_PROFILE", "false").lower() == "true"
    config["CHROME_PROFILE_PATH"] = os.getenv("CHROME_PROFILE_PATH", "/app/facebook_scraper/chrome_profile")
    config["HEADLESS"] = os.getenv("HEADLESS", "true").lower() == "true"
    
    # API URLs
    # Xác định BASE_API_URL dựa trên BACKEND_URL và BACKEND_PORT
    if config["BACKEND_PORT"] == "80":
        config["BASE_API_URL"] = f"{config['BACKEND_URL']}/api"
    else:
        config["BASE_API_URL"] = f"{config['BACKEND_URL']}:{config['BACKEND_PORT']}/api"
    
    config["FACEBOOK_POSTS_API_URL"] = f"{config['BASE_API_URL']}/facebook-posts"
    
    # Scraper Configurations 
    config["API_TIMEOUT"] = int(os.getenv("API_TIMEOUT", "600"))
    config["MAX_RETRIES"] = int(os.getenv("MAX_RETRIES", "3"))
    config["DEFAULT_POST_LIMIT"] = int(os.getenv("DEFAULT_POST_LIMIT", "10"))
    
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
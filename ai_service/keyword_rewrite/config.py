#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Quản lý cấu hình cho keyword_rewrite service từ file .env
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
KEYWORD_REWRITE_DIR = Path(__file__).resolve().parent  # Đường dẫn đến thư mục keyword_rewrite

# Đường dẫn đến các file .env
ROOT_ENV_FILE = ROOT_DIR / ".env"
AI_SERVICE_ENV_FILE = AI_SERVICE_DIR / ".env"
KEYWORD_REWRITE_ENV_FILE = KEYWORD_REWRITE_DIR / ".env"

# Biến để lưu trữ cấu hình
config = {}

def load_config():
    """
    Tải cấu hình từ các file .env theo thứ tự ưu tiên:
    1. .env trong thư mục keyword_rewrite (nếu có)
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
    
    # Tải file .env từ thư mục keyword_rewrite nếu có (ưu tiên cao nhất)
    if KEYWORD_REWRITE_ENV_FILE.exists():
        logger.info(f"Tải cấu hình từ file {KEYWORD_REWRITE_ENV_FILE}")
        load_dotenv(dotenv_path=KEYWORD_REWRITE_ENV_FILE, override=True)
    
    # Đọc các biến môi trường vào dict config
    # Database Configuration
    config["DB_HOST"] = os.getenv("DB_HOST", "localhost")
    config["DB_USER"] = os.getenv("DB_USERNAME", "root")
    config["DB_PASSWORD"] = os.getenv("DB_PASSWORD", "")
    config["DB_NAME"] = os.getenv("DB_DATABASE", "aimagazinedb")
    config["DB_PORT"] = int(os.getenv("DB_PORT", "3306"))
    config["DB_CONNECTION"] = os.getenv("DB_CONNECTION", "mysql")
    
    # API Configuration
    config["BACKEND_URL"] = os.getenv("BACKEND_URL", "http://localhost")
    config["BACKEND_PORT"] = os.getenv("BACKEND_PORT", "8000")
    config["AI_SERVICE_URL"] = os.getenv("AI_SERVICE_URL", "http://localhost:55025")
    
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
            
        # Đảm bảo OLLAMA_HOST có trong config trước khi sử dụng
        if "OLLAMA_HOST" not in config:
            config["OLLAMA_HOST"] = os.getenv("OLLAMA_HOST", "http://localhost:11434")
        if "OLLAMA_BASE_URL" not in config:
            config["OLLAMA_BASE_URL"] = os.getenv("OLLAMA_BASE_URL", "http://localhost:11434")
            
        # CHỈ cập nhật nếu BACKEND_URL đang là giá trị mặc định
        if is_linux and config["BACKEND_URL"] == "http://localhost":
            logger.info("Phát hiện Linux. Điều chỉnh BACKEND_URL...")
            config["BACKEND_URL"] = "http://172.17.0.1"
            config["DB_HOST"] = "172.17.0.1"
            config["OLLAMA_HOST"] = "http://172.17.0.1:11434"
            config["OLLAMA_BASE_URL"] = "http://172.17.0.1:11434"
        elif config["BACKEND_URL"] == "http://localhost":
            logger.info("Phát hiện Windows. Điều chỉnh BACKEND_URL...")
            config["BACKEND_URL"] = "http://host.docker.internal"
            config["BACKEND_PORT"] = "80"  # Sử dụng port 80 cho Apache virtual host
            config["DB_HOST"] = "host.docker.internal"
            config["OLLAMA_HOST"] = "http://host.docker.internal:11434"
            config["OLLAMA_BASE_URL"] = "http://host.docker.internal:11434"
        else:
            logger.info(f"Giữ nguyên BACKEND_URL: {config['BACKEND_URL']}")
            # Nếu đang chạy trên Windows, đảm bảo BACKEND_PORT là 80
            if not is_linux and "host.docker.internal" in config["BACKEND_URL"]:
                config["BACKEND_PORT"] = "80"
                logger.info("Điều chỉnh BACKEND_PORT thành 80 cho Apache virtual host")
        
        logger.info(f"BACKEND_URL cuối cùng: {config['BACKEND_URL']}")
        logger.info(f"DB_HOST cuối cùng: {config['DB_HOST']}")
        if "OLLAMA_HOST" in config:
            logger.info(f"OLLAMA_HOST cuối cùng: {config['OLLAMA_HOST']}")
    
    # Service Configuration
    config["PORT_KEYWORD_REWRITE"] = int(os.getenv("PORT_KEYWORD_REWRITE", "5003"))
    config["HOST"] = os.getenv("HOST", "0.0.0.0")
    config["DEBUG"] = os.getenv("DEBUG", "False").lower() == "true"
    
    # AI Configuration
    config["OLLAMA_HOST"] = os.getenv("OLLAMA_HOST", "http://localhost:11434")
    config["OLLAMA_BASE_URL"] = os.getenv("OLLAMA_BASE_URL", "http://localhost:11434")
    config["OLLAMA_MODEL"] = os.getenv("OLLAMA_MODEL", "gemma2:latest")
    config["MODEL_NAME"] = os.getenv("MODEL_NAME", "gemma2:latest")
    
    config["GEMINI_API_KEY"] = os.getenv("GEMINI_API_KEY", "")
    config["GEMINI_MODEL"] = os.getenv("GEMINI_MODEL", "gemini-1.5-flash-latest")
    
    config["OPENAI_API_KEY"] = os.getenv("OPENAI_API_KEY", "")
    
    config["DEFAULT_PROVIDER"] = os.getenv("DEFAULT_PROVIDER", "gemini")
    
    # API URLs
    # Xác định BASE_API_URL dựa trên BACKEND_URL và BACKEND_PORT
    if config["BACKEND_PORT"] == "80":
        config["BASE_API_URL"] = f"{config['BACKEND_URL']}/api"
    else:
        config["BASE_API_URL"] = f"{config['BACKEND_URL']}:{config['BACKEND_PORT']}/api"
    
    config["ARTICLES_API_URL"] = f"{config['BASE_API_URL']}/articles"
    config["KEYWORD_REWRITES_API_URL"] = f"{config['BASE_API_URL']}/keyword-rewrites"
    
    # Keyword Rewrite configuration
    config["API_TIMEOUT"] = int(os.getenv("API_TIMEOUT", "600"))
    config["MAX_RETRIES"] = int(os.getenv("MAX_RETRIES", "3"))
    config["MAX_TEXT_SIZE"] = int(os.getenv("MAX_TEXT_SIZE", "8000"))
    
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
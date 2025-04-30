#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Đây là module chính cho scraper bài viết tin tức
Quy trình: 
1. Tìm kiếm URL bài viết từ google_news.py 
2. Trích xuất nội dung từ URL bằng scrape_articles_selenium.py
3. Lưu kết quả vào file JSON trong thư mục output
"""

import os
import sys
import json
import uuid
import time
import shutil
import logging
import argparse
import requests
from pathlib import Path
from datetime import datetime, timedelta
from urllib.parse import urlparse
import glob
import traceback
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import random
from dotenv import load_dotenv
import re
import mysql.connector
from mysql.connector import Error
from unidecode import unidecode

# Import cấu hình từ module config
from config import get_config, reload_config

# Import các module nội bộ
from google_news import (
    fetch_categories_from_backend,
    search_with_category,
    process_all_categories,
    save_article_to_json,
    import_article_to_backend,
    get_category_by_id
)
from scrape_articles_selenium import extract_article_content

# Tải cấu hình từ file .env thông qua module config
config = get_config()

# Thông tin kết nối database từ cấu hình
DB_HOST = config['DB_HOST']
DB_USER = config['DB_USER']
DB_PASSWORD = config['DB_PASSWORD']
DB_NAME = config['DB_NAME']
DB_PORT = config['DB_PORT']

# Config kết nối database
DB_CONFIG = {
    "host": DB_HOST,
    "user": DB_USER,
    "password": DB_PASSWORD,
    "database": DB_NAME,
    "port": DB_PORT
}

# Cấu hình logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(f"scraper_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)
logger.setLevel(logging.INFO)

# 🔹 Các thông số mặc định từ cấu hình
SCRAPER_DIR = os.path.dirname(os.path.abspath(__file__))
TEMP_DIR = os.path.join(SCRAPER_DIR, "temp")
OUTPUT_DIR = os.path.join(SCRAPER_DIR, "output")
DEFAULT_BATCH_SIZE = config.get("DEFAULT_BATCH_SIZE", 5)
# Số ngày để giữ lại log files và output files
RETENTION_DAYS = config.get("RETENTION_DAYS", 7)

# 🔹 Laravel Backend API URLs từ cấu hình
BACKEND_URL = config['BACKEND_URL']
BACKEND_PORT = config['BACKEND_PORT']
BASE_API_URL = config['BASE_API_URL']
CATEGORIES_API_URL = config['CATEGORIES_API_URL']
ARTICLES_API_URL = config['ARTICLES_API_URL']
ARTICLES_BATCH_API_URL = config['ARTICLES_BATCH_API_URL']
ARTICLES_IMPORT_API_URL = config['ARTICLES_IMPORT_API_URL']
ARTICLES_CHECK_API_URL = config['ARTICLES_CHECK_API_URL']

# Các biến toàn cục
# Cache lưu các URL đã xử lý để tránh trùng lặp
processed_urls = set()

def check_api_keys():
    """
    Kiểm tra các API keys từ file .env
    """
    config = get_config()  # Tải lại cấu hình để có thông tin mới nhất
    api_keys_status = {}
    
    # Kiểm tra WorldNewsAPI
    worldnews_api_key = config.get('WORLDNEWS_API_KEY', '')
    if worldnews_api_key:
        api_keys_status['WorldNewsAPI'] = True
    else:
        api_keys_status['WorldNewsAPI'] = False
        
    # Kiểm tra Currents API
    currents_api_key = config.get('CURRENTS_API_KEY', '')
    if currents_api_key:
        api_keys_status['CurrentsAPI'] = True
    else:
        api_keys_status['CurrentsAPI'] = False
    
    # In thông tin
    logger.info("=== Trạng thái API keys ===")
    for api_name, status in api_keys_status.items():
        if status:
            logger.info(f"✅ {api_name}: OK")
        else:
            logger.warning(f"⚠️ {api_name}: Không tìm thấy API key")
    
    return api_keys_status

def save_articles_to_file(articles, output_file=None):
    """
    Lưu danh sách bài viết vào file JSON
    
    Args:
        articles (list): Danh sách bài viết
        output_file (str): Đường dẫn file đầu ra
        
    Returns:
        str: Đường dẫn đến file đã lưu
    """
    if not articles:
        logger.warning("Không có bài viết nào để lưu!")
        return None
    
    # Tạo thư mục đầu ra nếu chưa tồn tại
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    # Tạo tên file mặc định nếu không được cung cấp
    if not output_file:
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        output_file = os.path.join(OUTPUT_DIR, f"enriched_articles_{timestamp}.json")
    
    # Chuẩn hóa dữ liệu trước khi lưu
    normalized_articles = []
    for article in articles:
        normalized = article.copy()
        
        # Đảm bảo source_name là chuỗi
        if isinstance(normalized.get("source_name"), dict):
            normalized["source_name"] = normalized["source_name"].get("name", "Unknown Source")
        
        # Đảm bảo meta_data là chuỗi JSON
        if isinstance(normalized.get("meta_data"), dict):
            normalized["meta_data"] = json.dumps(normalized["meta_data"])
        
        # Đảm bảo summary không bao giờ là None
        if normalized.get("summary") is None:
            normalized["summary"] = ""
        
        # Đảm bảo content không bao giờ là None
        if normalized.get("content") is None:
            normalized["content"] = ""
        
        # Đảm bảo published_at có định dạng chuẩn
        if normalized.get("published_at"):
            try:
                # Chuẩn hóa định dạng ngày tháng
                date_obj = datetime.fromisoformat(normalized["published_at"].replace('Z', '+00:00'))
                normalized["published_at"] = date_obj.isoformat()
            except (ValueError, TypeError):
                # Nếu không phân tích được, sử dụng thời gian hiện tại
                normalized["published_at"] = datetime.now().isoformat()
        
        normalized_articles.append(normalized)
    
    # Lưu bài viết vào file JSON
    with open(output_file, "w", encoding="utf-8") as f:
        json.dump(normalized_articles, f, ensure_ascii=False, indent=4)
    
    logger.info(f"[OK] Đã lưu {len(normalized_articles)} bài viết vào {output_file}")
    return output_file

def send_to_backend(articles, batch_size=DEFAULT_BATCH_SIZE, auto_send=True):
    """
    Gửi bài viết tới backend API
    
    Args:
        articles (list): Danh sách bài viết
        batch_size (int): Số lượng bài viết gửi trong mỗi request
        auto_send (bool): Tự động gửi không cần xác nhận (luôn True)
        
    Returns:
        bool: Trạng thái thành công
    """
    if not articles:
        logger.warning("Không có bài viết nào để gửi!")
        return False
    
    # Ghi log tổng số bài viết cần gửi
    logger.info(f"Chuẩn bị gửi {len(articles)} bài viết tới backend")
    
    # Debug: Kiểm tra API URL
    api_url = ARTICLES_IMPORT_API_URL
    logger.info(f"API URL: {api_url}")
    logger.debug(f"BASE_API_URL: {get_config('BASE_API_URL')}")
    logger.debug(f"BACKEND_URL: {get_config('BACKEND_URL')}")
    logger.debug(f"BACKEND_PORT: {get_config('BACKEND_PORT')}")
    
    # Debug tiêu đề và URL của bài viết đầu tiên
    if len(articles) > 0:
        logger.info(f"Bài viết đầu tiên: {articles[0].get('title', 'N/A')}")
        logger.info(f"Source URL: {articles[0].get('source_url', 'N/A')}")
    
    # Chuẩn hóa dữ liệu trước khi gửi
    normalized_articles = []
    for article in articles:
        normalized = article.copy()
        
        # Đảm bảo source_name là chuỗi
        if isinstance(normalized.get("source_name"), dict):
            normalized["source_name"] = normalized["source_name"].get("name", "Unknown Source")
        
        # Đảm bảo meta_data là chuỗi JSON
        if isinstance(normalized.get("meta_data"), dict):
            try:
                normalized["meta_data"] = json.dumps(normalized["meta_data"], ensure_ascii=False)
            except Exception as e:
                logger.warning(f"Lỗi khi chuyển đổi meta_data sang JSON: {str(e)}")
                normalized["meta_data"] = json.dumps({"error": "Invalid metadata"})
        
        # Đảm bảo summary không bao giờ là None
        if normalized.get("summary") is None:
            normalized["summary"] = ""
        
        # Đảm bảo content không bao giờ là None
        if normalized.get("content") is None:
            normalized["content"] = ""
        
        # Đảm bảo published_at có định dạng chuẩn
        if normalized.get("published_at"):
            try:
                # Chuẩn hóa định dạng ngày tháng
                date_obj = datetime.fromisoformat(normalized["published_at"].replace('Z', '+00:00'))
                normalized["published_at"] = date_obj.isoformat()
            except (ValueError, TypeError):
                # Nếu không phân tích được, sử dụng thời gian hiện tại
                normalized["published_at"] = datetime.now().isoformat()
        else:
            # Nếu không có published_at, sử dụng thời gian hiện tại
                normalized["published_at"] = datetime.now().isoformat()
                
        normalized_articles.append(normalized)
    
    # Chia thành các batch để gửi
    batches = [normalized_articles[i:i + batch_size] for i in range(0, len(normalized_articles), batch_size)]
    logger.info(f"Gửi {len(normalized_articles)} bài viết tới backend trong {len(batches)} batches...")
    
    total_success = True
    total_imported = 0
    total_skipped = 0
    
    for i, batch in enumerate(batches, 1):
        logger.info(f"Gửi batch {i}/{len(batches)} ({len(batch)} bài viết)")
        
        # In ra tiêu đề của bài đầu tiên trong batch để debug
        if len(batch) > 0:
            logger.debug(f"Bài viết đầu tiên trong batch: {batch[0].get('title', 'Không có tiêu đề')}")
        
        # Chuẩn bị payload theo đúng định dạng API yêu cầu
        payload = {
            "articles": batch
        }
        
        # Thiết lập header
        headers = {
            "Content-Type": "application/json",
            "Accept": "application/json"
        }
        
        # Gửi request, với cơ chế thử lại nếu lỗi
        max_retries = 3
        retry_count = 0
        retry_delay = 2  # Giây
        
        while retry_count < max_retries:
            try:
                # Sử dụng endpoint import từ cấu hình
                api_url = ARTICLES_IMPORT_API_URL
                logger.info(f"Gửi dữ liệu đến API: {api_url}")
                
                # Log payload size
                payload_size = len(json.dumps(payload))
                logger.debug(f"Kích thước payload: {payload_size} bytes")
                
                response = requests.post(
                    api_url,
                    json=payload,
                    headers=headers,
                    timeout=30
                )
                
                # In ra response status và phần đầu của response body để debug
                logger.info(f"API Response Status: {response.status_code}")
                response_text = response.text[:200] + "..." if len(response.text) > 200 else response.text
                logger.info(f"API Response Text: {response_text}")
                
                if response.status_code == 200 or response.status_code == 201:
                    logger.info(f"[OK] Batch {i}: Gửi thành công")
                    # Phân tích phản hồi để tính số bài viết đã import
                    try:
                        result = response.json()
                        success = result.get('success', 0)
                        skipped = result.get('skipped', 0)
                        total_imported += int(success) if isinstance(success, (int, str)) else 0
                        total_skipped += int(skipped) if isinstance(skipped, (int, str)) else 0
                        logger.info(f"[INFO] Batch {i}: {success} bài viết đã import, {skipped} bài viết bị bỏ qua")
                    except Exception as e:
                        logger.warning(f"Không thể phân tích JSON response: {str(e)}")
                    break
                else:
                    error_msg = response.text[:100] + "..." if len(response.text) > 100 else response.text
                    logger.error(f"[ERROR] Batch {i}: Lỗi {response.status_code} - {error_msg}")
                    
                    # Thử lại sau thời gian delay
                    retry_count += 1
                    if retry_count < max_retries:
                        logger.info(f"Thử lại sau {retry_delay} giây...")
                        time.sleep(retry_delay)
                        # Tăng thời gian delay cho lần thử tiếp theo
                        retry_delay *= 2
                    else:
                        logger.error(f"[ERROR] Đã thử lại {max_retries} lần không thành công cho batch {i}")
                        total_success = False
                        
            except Exception as e:
                logger.error(f"[ERROR] Batch {i}: Exception - {str(e)}")
                # Log stack trace
                import traceback
                logger.error(f"Stack trace: {traceback.format_exc()}")
                
                retry_count += 1
                if retry_count < max_retries:
                    logger.info(f"Thử lại sau {retry_delay} giây...")
                    time.sleep(retry_delay)
                    retry_delay *= 2
                else:
                    logger.error(f"[ERROR] Đã thử lại {max_retries} lần không thành công cho batch {i}")
                    total_success = False
    
    if total_success:
        logger.info(f"[OK] Tổng cộng: {total_imported} bài viết đã được import thành công, {total_skipped} bài viết bị bỏ qua")
    else:
        logger.warning(f"[WARN] Có lỗi xảy ra khi gửi bài viết tới backend. Đã import: {total_imported}, Đã bỏ qua: {total_skipped}")
        
    return total_success

def cleanup_temp_files():
    """
    Dọn dẹp các file tạm thời
    """
    if os.path.exists(TEMP_DIR):
        try:
            shutil.rmtree(TEMP_DIR)
            logger.info(f"[OK] Đã dọn dẹp thư mục tạm: {TEMP_DIR}")
        except Exception as e:
            logger.error(f"[ERROR] Lỗi khi dọn dẹp thư mục tạm: {str(e)}")

def cleanup_old_files(retention_days=RETENTION_DAYS):
    """
    Dọn dẹp các file cũ trong thư mục output
    
    Args:
        retention_days (int): Số ngày giữ lại files (mặc định: 2)
    """
    if not os.path.exists(OUTPUT_DIR):
        return
    
    cutoff_date = time.time() - (retention_days * 24 * 60 * 60)
    logger.info(f"Dọn dẹp các file cũ hơn {retention_days} ngày trong thư mục {OUTPUT_DIR}")
    
    count = 0
    for filename in os.listdir(OUTPUT_DIR):
        filepath = os.path.join(OUTPUT_DIR, filename)
        if os.path.isfile(filepath):
            file_time = os.path.getmtime(filepath)
            if file_time < cutoff_date:
                try:
                    os.remove(filepath)
                    count += 1
                    logger.debug(f"Đã xóa file cũ: {filename}")
                except Exception as e:
                    logger.warning(f"Không thể xóa file {filename}: {str(e)}")
    
    if count > 0:
        logger.info(f"Đã xóa {count} file cũ")
    else:
        logger.info("Không có file nào cần xóa")

def process_category(category_id, category_name, max_articles=2):
    """
    Xử lý một danh mục cụ thể và trích xuất bài viết
    
    Args:
        category_id: ID của danh mục
        category_name: Tên của danh mục
        max_articles: Số bài viết tối đa cần lấy
        
    Returns:
        dict: Danh sách các bài viết đã tìm thấy cho danh mục
    """
    articles = []
    
    for i in range(1, max_articles + 1):
        logger.info(f"Tìm kiếm bài viết thứ {i}/{max_articles} cho danh mục: {category_name}")
        
        try:
            article_data = search_with_category(category_id)
            
            if article_data and "url" in article_data:
                article_url = article_data["url"]
                
                # Kiểm tra xem URL này đã được xử lý chưa
                if article_url in processed_urls:
                    logger.warning(f"URL đã xử lý trước đó, bỏ qua: {article_url}")
                    continue
                
                # Đánh dấu URL này đã được xử lý
                processed_urls.add(article_url)
                
                articles.append(article_data)
                
                logger.info(f"Đã tìm thấy bài viết: {article_data.get('title', 'Không có tiêu đề')}")
                logger.info(f"URL: {article_url}")
                logger.info(f"Đã lưu vào: {article_data.get('file_path', 'Không lưu file')}")
            else:
                logger.warning(f"Không tìm thấy thêm bài viết nào cho danh mục: {category_name}")
                break
            
            # Thêm khoảng thời gian nghỉ giữa các lần tìm kiếm để tránh bị chặn
            if i < max_articles:
                time.sleep(2)
                
        except Exception as e:
            logger.error(f"Lỗi khi xử lý bài viết thứ {i} cho danh mục {category_name}: {str(e)}")
            logger.error(traceback.format_exc())
    
    return articles

def find_and_process_all_categories(max_articles_per_category=2):
    """
    Tìm kiếm và xử lý tất cả các danh mục từ backend.
    Mỗi danh mục sẽ được tìm kiếm bài viết và lưu trữ.
    
    Args:
        max_articles_per_category (int): Số bài viết tối đa cho mỗi danh mục
        
    Returns:
        dict: Kết quả xử lý các danh mục
    """
    results = {
        'success': 0,
        'failed': 0,
        'articles_by_category': {},
        'all_articles': [],
        'total_articles': 0
    }
    
    logger.info(f"Bắt đầu tìm kiếm và xử lý bài viết cho các danh mục (tối đa {max_articles_per_category} bài/danh mục)")
    
    # Lấy danh sách các danh mục từ backend
    categories = fetch_categories_from_backend()
    
    if not categories:
        # Backup plan when backend is not available - use hardcoded categories
        logger.warning("Không thể lấy danh mục từ backend, sử dụng danh mục mẫu...")
        categories = [
            {"id": 1, "name": "Thời sự", "slug": "thoi-su"},
            {"id": 2, "name": "Thế giới", "slug": "the-gioi"},
            {"id": 3, "name": "Kinh doanh", "slug": "kinh-doanh"},
            {"id": 4, "name": "Giải trí", "slug": "giai-tri"},
            {"id": 5, "name": "Thể thao", "slug": "the-thao"},
            {"id": 6, "name": "Pháp luật", "slug": "phap-luat"},
            {"id": 7, "name": "Giáo dục", "slug": "giao-duc"},
            {"id": 8, "name": "Sức khỏe", "slug": "suc-khoe"},
            {"id": 9, "name": "Đời sống", "slug": "doi-song"},
            {"id": 10, "name": "Du lịch", "slug": "du-lich"}
        ]
        logger.info(f"Sử dụng {len(categories)} danh mục mẫu")
        
    # Xử lý từng danh mục
    total_categories = len(categories)
    logger.info(f"Đã tìm thấy {total_categories} danh mục để xử lý")
    
    # Xử lý tất cả các danh mục theo giới hạn
    categories_processed = 0
    
    for category in categories:
        if categories_processed >= total_categories:
            break
            
        category_id = category.get('id')
        category_name = category.get('name')
        
        if not category_id or not category_name:
            logger.warning(f"Danh mục không hợp lệ, thiếu ID hoặc tên: {category}")
            results['failed'] += 1
            continue
        
        logger.info(f"Xử lý danh mục: ID={category_id}, Tên={category_name}")
        
        try:
            # Tìm kiếm bài viết cho danh mục này
            articles = process_category(category_id, category_name, max_articles_per_category)
            
            if not articles or len(articles) == 0:
                logger.warning(f"Không tìm thấy bài viết nào cho danh mục: {category_name}")
                results['failed'] += 1
                continue
            
            # Thêm bài viết vào kết quả
            results['articles_by_category'][category_id] = articles
            results['all_articles'].extend(articles)
            results['success'] += 1
            results['total_articles'] += len(articles)
            
            logger.info(f"Đã xử lý thành công danh mục: {category_name}, số bài viết tìm được: {len(articles)}")
            
        except Exception as e:
            logger.error(f"Lỗi khi xử lý danh mục {category_name}: {str(e)}")
            results['failed'] += 1
            
        categories_processed += 1
    
    # Tổng kết
    logger.info(f"\nĐã xử lý {categories_processed} danh mục")
    logger.info(f"Thành công: {results['success']} danh mục, Thất bại: {results['failed']} danh mục")
    logger.info(f"Tổng số bài viết đã tìm được: {results['total_articles']}")
    
    # Tự động import các bài viết vào database
    if results['all_articles'] and len(results['all_articles']) > 0:
        logger.info(f"Tự động import {len(results['all_articles'])} bài viết vào database")
        send_to_backend(results['all_articles'])
    
    return results

def import_all_to_backend(directory=None):
    """
    Import tất cả các file JSON từ thư mục output vào backend
    
    Args:
        directory (str): Thư mục chứa các file JSON (mặc định: OUTPUT_DIR)
        
    Returns:
        tuple: (success_count, failed_count) - Số lượng file thành công và thất bại
    """
    if directory is None:
        directory = OUTPUT_DIR
    
    if not os.path.exists(directory):
        logger.error(f"Thư mục không tồn tại: {directory}")
        return 0, 0
    
    logger.info(f"Bắt đầu import tất cả các file JSON từ thư mục: {directory}")
    
    # Tìm tất cả các file JSON trong thư mục
    json_files = [f for f in os.listdir(directory) if f.endswith('.json')]
    
    if not json_files:
        logger.warning(f"Không tìm thấy file JSON nào trong thư mục: {directory}")
        return 0, 0
    
    logger.info(f"Tìm thấy {len(json_files)} file JSON")
    
    success_count = 0
    failed_count = 0
    
    for json_file in json_files:
        filepath = os.path.join(directory, json_file)
        logger.info(f"Đang xử lý file: {json_file}")
        
        try:
            # Đọc dữ liệu từ file JSON
            with open(filepath, 'r', encoding='utf-8') as f:
                data = json.load(f)
            
            # Kiểm tra xem dữ liệu có phải là dictionary với các trường cần thiết không
            if isinstance(data, dict) and 'category_id' in data and 'title' in data and 'content' in data:
                # Import bài viết vào backend
                result = import_article_to_backend(
                    category_id=data['category_id'],
                    article_url=data.get('url', ''),
                    title=data['title'],
                    content=data['content']
                )
                
                if result:
                    logger.info(f"Import thành công file: {json_file}")
                    success_count += 1
                    # Xóa file JSON sau khi import thành công
                    try:
                        os.remove(filepath)
                        logger.info(f"Đã xóa file JSON sau khi import thành công: {json_file}")
                    except Exception as e:
                        logger.warning(f"Không thể xóa file {json_file}: {str(e)}")
                else:
                    logger.error(f"Import thất bại file: {json_file}")
                    failed_count += 1
            else:
                logger.warning(f"File không đúng định dạng: {json_file}")
                failed_count += 1
        
        except Exception as e:
            logger.error(f"Lỗi khi xử lý file {json_file}: {str(e)}")
            failed_count += 1
    
    logger.info(f"Kết quả import: Thành công: {success_count}, Thất bại: {failed_count}")
    return success_count, failed_count

def save_all_articles_to_single_file(results):
    """
    Lưu tất cả bài viết từ tất cả các danh mục vào một file JSON duy nhất
    :param results: Kết quả từ hàm find_and_process_all_categories
    :return: Đường dẫn đến file JSON đã lưu
    """
    logger.info(f"Bắt đầu lưu tất cả bài viết vào file JSON duy nhất, dữ liệu đầu vào: {type(results)}")
    
    # Kiểm tra dữ liệu đầu vào
    if not isinstance(results, dict):
        logger.error(f"Dữ liệu đầu vào không hợp lệ: {type(results)}")
        return None
    
    # Log cấu trúc của results để debug
    logger.info(f"Cấu trúc results: {list(results.keys())}")
    
    # Ưu tiên sử dụng all_articles nếu có
    all_articles = []
    
    if "all_articles" in results and isinstance(results["all_articles"], list) and results["all_articles"]:
        all_articles_raw = results["all_articles"]
        logger.info(f"Sử dụng {len(all_articles_raw)} bài viết từ all_articles")
        
        # Thu thập bài viết với nội dung đầy đủ
        for article in all_articles_raw:
            if not isinstance(article, dict):
                logger.warning(f"Bỏ qua bài viết không hợp lệ: {type(article)}")
                continue
                
            # Đọc nội dung từ file JSON nếu bài viết không có trường 'content'
            article_copy = article.copy()
            if "content" not in article_copy and "json_filepath" in article_copy:
                try:
                    json_content = read_json_file(article_copy["json_filepath"])
                    if json_content and "content" in json_content:
                        # Cập nhật nội dung từ file JSON
                        article_copy.update(json_content)
                        logger.info(f"Đã lấy nội dung từ file: {article_copy['json_filepath']}")
                except Exception as e:
                    logger.error(f"Lỗi khi đọc file {article_copy['json_filepath']}: {str(e)}")
            
            # Thêm vào danh sách nếu có nội dung
            if article_copy.get("content"):
                all_articles.append(article_copy)
            else:
                logger.warning(f"Bỏ qua bài viết không có nội dung: {article_copy.get('title', 'Không tiêu đề')}")
    else:
        # Thu thập bài viết từ cấu trúc articles_by_category
        if "articles_by_category" in results and isinstance(results["articles_by_category"], dict):
            articles_by_category = results["articles_by_category"]
            
            # Thu thập bài viết từ mỗi danh mục
            for category_name, articles in articles_by_category.items():
                if not isinstance(articles, list):
                    logger.warning(f"Bỏ qua danh mục {category_name} vì dữ liệu không hợp lệ: {type(articles)}")
                    continue
                
                logger.info(f"Xử lý {len(articles)} bài viết từ danh mục {category_name}")
                
                for article in articles:
                    if not isinstance(article, dict):
                        logger.warning(f"Bỏ qua bài viết không hợp lệ: {type(article)}")
                        continue
                    
                    # Tạo bản sao và thêm thông tin danh mục
                    article_copy = article.copy()
                    article_copy["category_name"] = category_name
                    
                    # Đọc nội dung từ file JSON nếu bài viết không có trường 'content'
                    if "content" not in article_copy and "json_filepath" in article_copy:
                        try:
                            json_content = read_json_file(article_copy["json_filepath"])
                            if json_content and "content" in json_content:
                                # Cập nhật nội dung từ file JSON
                                article_copy.update(json_content)
                                logger.info(f"Đã lấy nội dung từ file: {article_copy['json_filepath']}")
                        except Exception as e:
                            logger.error(f"Lỗi khi đọc file {article_copy['json_filepath']}: {str(e)}")
                    
                    # Thêm vào danh sách nếu có nội dung
                    if article_copy.get("content"):
                        all_articles.append(article_copy)
                    else:
                        logger.warning(f"Bỏ qua bài viết không có nội dung: {article_copy.get('title', 'Không tiêu đề')}")
    
    # Kiểm tra số lượng bài viết sau khi thu thập
    if not all_articles:
        logger.warning("Không có bài viết hợp lệ để lưu vào file JSON duy nhất")
        return None
    
    # Tạo tên file duy nhất
    current_time = datetime.now().strftime("%Y%m%d_%H%M%S")
    unique_id = str(uuid.uuid4())[:8]  # Lấy 8 ký tự đầu của UUID
    
    # Kiểm tra xem đường dẫn /app/output có tồn tại không (Docker environment)
    docker_output = "/app/output"
    if os.path.exists(docker_output) and os.path.isdir(docker_output):
        output_file = os.path.join(docker_output, f"all_articles_{current_time}_{unique_id}.json")
        # Bảo đảm cũng tạo bản sao trong OUTPUT_DIR để import_json_file_to_backend có thể tìm thấy
        local_output_file = os.path.join(OUTPUT_DIR, f"all_articles_{current_time}_{unique_id}.json")
    else:
        output_file = os.path.join(OUTPUT_DIR, f"all_articles_{current_time}_{unique_id}.json")
        local_output_file = output_file
    
    # Đảm bảo thư mục output tồn tại
    os.makedirs(os.path.dirname(output_file), exist_ok=True)
    if output_file != local_output_file:
        os.makedirs(os.path.dirname(local_output_file), exist_ok=True)
    
    # Lưu vào file JSON
    try:
        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(all_articles, f, ensure_ascii=False, indent=2)
        
        logger.info(f"Đã lưu {len(all_articles)} bài viết vào file JSON duy nhất: {output_file}")
        
        # Nếu đang chạy trong Docker, tạo bản sao để đảm bảo import
        if output_file != local_output_file:
            shutil.copy2(output_file, local_output_file)
            logger.info(f"Đã tạo bản sao tại: {local_output_file} để đảm bảo import vào DB")
        
        # In thông tin bài viết đầu tiên để debug
        if all_articles:
            first_article = all_articles[0]
            logger.info(f"Bài viết mẫu: Title={first_article.get('title', 'Không tiêu đề')}, Content length={len(first_article.get('content', ''))}")
            
        return local_output_file  # Trả về đường dẫn đến file có thể đọc được từ hàm import
    except Exception as e:
        logger.error(f"Lỗi khi lưu file JSON: {str(e)}")
        logger.error(traceback.format_exc())
        return None

def read_json_file(file_path):
    """
    Đọc nội dung từ file JSON
    :param file_path: Đường dẫn đến file JSON
    :return: Dữ liệu từ file JSON hoặc None nếu lỗi
    """
    if not os.path.exists(file_path):
        logger.error(f"File không tồn tại: {file_path}")
        return None
        
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
        return data
    except Exception as e:
        logger.error(f"Lỗi khi đọc file JSON: {str(e)}")
        return None

def import_json_file_to_backend(json_file_path):
    """
    Import tất cả bài viết từ file JSON vào backend
    :param json_file_path: Đường dẫn đến file JSON
    :return: tuple (số bài viết import thành công, số bài viết thất bại)
    """
    logger.info(f"Đang import bài viết từ file: {json_file_path}")
    
    # Kiểm tra sự tồn tại của file
    file_exists = os.path.exists(json_file_path)
    
    # Thử tìm file ở đường dẫn /app/output/ nếu không tìm thấy ở đường dẫn gốc
    if not file_exists and not json_file_path.startswith('/app/output/'):
        filename = os.path.basename(json_file_path)
        docker_path = f"/app/output/{filename}"
        if os.path.exists(docker_path):
            logger.info(f"Không tìm thấy file tại {json_file_path}, nhưng tìm thấy tại {docker_path}")
            json_file_path = docker_path
            file_exists = True
    
    # Thử tìm file ở OUTPUT_DIR nếu không tìm thấy ở đường dẫn /app/output/
    if not file_exists and json_file_path.startswith('/app/output/'):
        filename = os.path.basename(json_file_path)
        local_path = os.path.join(OUTPUT_DIR, filename)
        if os.path.exists(local_path):
            logger.info(f"Không tìm thấy file tại {json_file_path}, nhưng tìm thấy tại {local_path}")
            json_file_path = local_path
            file_exists = True
    
    if not file_exists:
        logger.error(f"File không tồn tại: {json_file_path}")
        return 0, 0
    
    # Đọc file JSON
    try:
        with open(json_file_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        logger.error(f"Lỗi khi đọc file JSON: {str(e)}")
        logger.error(traceback.format_exc())
        return 0, 0
    
    # Kiểm tra dữ liệu là list
    if not isinstance(data, list):
        logger.error(f"Dữ liệu không hợp lệ, phải là list: {type(data)}")
        return 0, 0
    
    # Chuẩn bị dữ liệu để gửi lên API
    articles_to_import = []
    
    for article in data:
        if not isinstance(article, dict):
            logger.warning(f"Bỏ qua bài viết không hợp lệ, không phải dict: {type(article)}")
            continue
            
        # Đọc nội dung từ file lưu trữ nếu cần
        article_content = article.get("content", "")
        if not article_content and "json_filepath" in article:
            json_data = read_json_file(article["json_filepath"])
            if json_data and isinstance(json_data, dict):
                article_content = json_data.get("content", "")
                # Cập nhật thông tin khác từ file JSON
                if not article.get("title") and json_data.get("title"):
                    article["title"] = json_data["title"]
                if not article.get("summary") and json_data.get("summary"):
                    article["summary"] = json_data["summary"]
                
        # Chuẩn bị dữ liệu bài viết
        article_data = {
            "title": article.get("title", ""),
            "slug": generate_slug(article.get("title", "Không tiêu đề")),
            "summary": article.get("summary", ""),
            "content": article_content,
            "source_name": article.get("source_name", ""),
            "source_url": article.get("source_url", article.get("url", "")),
            "source_icon": article.get("source_icon", ""),
            "published_at": article.get("published_at", datetime.now().isoformat()),
            "meta_data": article.get("meta_data", {}),
            "category": article.get("category_id", 1)  # Đảm bảo sử dụng category_id, không phải category
        }
        
        # Tạo summary từ nội dung nếu không có
        if not article_data["summary"] and article_data["content"]:
            sentences = re.split(r'[.!?]+', article_data["content"])
            if len(sentences) >= 2:
                article_data["summary"] = '. '.join(s.strip() for s in sentences[:2] if s.strip()) + '.'
            else:
                article_data["summary"] = sentences[0] if sentences else ""
                
        # Tạo source_name từ URL nếu không có
        if not article_data["source_name"] and article_data["source_url"]:
            try:
                parsed_url = urlparse(article_data["source_url"])
                article_data["source_name"] = parsed_url.netloc
            except:
                article_data["source_name"] = "Không rõ"
                
        # Tạo source_icon từ domain nếu không có
        if not article_data["source_icon"] and article_data["source_url"]:
            try:
                parsed_url = urlparse(article_data["source_url"])
                domain = parsed_url.netloc
                article_data["source_icon"] = f"https://www.google.com/s2/favicons?domain={domain}"
            except:
                article_data["source_icon"] = ""
        
        # Kiểm tra nội dung bài viết
        if not article_data["content"]:
            logger.warning(f"Bỏ qua bài viết không có nội dung: {article_data['title']}")
            continue
            
        articles_to_import.append(article_data)
    
    if not articles_to_import:
        logger.warning("Không có bài viết hợp lệ để import")
        return 0, 0
    
    logger.info(f"Đang import {len(articles_to_import)} bài viết")
    
    # In phần đầu của bài viết đầu tiên để debug
    if articles_to_import:
        first_article = articles_to_import[0]
        logger.info(f"Mẫu bài viết đầu tiên: Title={first_article['title']}, Content length={len(first_article['content'])}, Source={first_article['source_name']}")
    
    # Chuẩn bị payload theo đúng định dạng API yêu cầu
    payload = {
        "articles": articles_to_import
    }
    
    # Thử import với endpoint import
    try:
        logger.info(f"Thử import với endpoint: {ARTICLES_IMPORT_API_URL}")
        
        # Gọi API import
        response = requests.post(
            ARTICLES_IMPORT_API_URL,
            json=payload,
            headers={"Content-Type": "application/json", "Accept": "application/json"}
        )
        
        logger.info(f"Phản hồi từ endpoint import: Status code: {response.status_code}")
        logger.info(f"Phản hồi nội dung: {response.text[:500]}")
        
        if response.status_code == 200:
            try:
                result = response.json()
                success = result.get("success", 0) or (len(articles_to_import) - result.get("skipped", 0))
                failed = result.get("failed", 0) or result.get("skipped", 0)
                logger.info(f"Import thành công với endpoint import: {success} thành công, {failed} thất bại")
                
                # Xóa file JSON sau khi import thành công nếu có bài viết được import
                if success > 0:
                    try:
                        os.remove(json_file_path)
                        logger.info(f"Đã xóa file JSON sau khi import thành công: {json_file_path}")
                    except Exception as e:
                        logger.warning(f"Không thể xóa file {json_file_path}: {str(e)}")
                return success, failed
            except Exception as e:
                logger.error(f"Lỗi khi xử lý phản hồi từ endpoint import: {str(e)}")
        else:
            logger.warning(f"Import với endpoint import thất bại. Status code: {response.status_code}")
    except Exception as e:
        logger.error(f"Lỗi khi gọi endpoint import: {str(e)}")
        logger.error(traceback.format_exc())
    
    # Nếu endpoint import thất bại, thử import trực tiếp vào DB
    logger.info("Thử import trực tiếp vào database...")
    success, failed = import_articles_to_database_directly(articles_to_import)
    
    # Xóa file JSON sau khi import thành công nếu có bài viết được import
    if success > 0:
        try:
            os.remove(json_file_path)
            logger.info(f"Đã xóa file JSON sau khi import thành công: {json_file_path}")
        except Exception as e:
            logger.warning(f"Không thể xóa file {json_file_path}: {str(e)}")
    
    return success, failed

def import_articles_to_database_directly(articles):
    """
    Import bài viết trực tiếp vào database (fallback khi API thất bại)
    :param articles: Danh sách bài viết cần import
    :return: tuple (số bài viết import thành công, số bài viết thất bại)
    """
    if not check_db_connection():
        logger.error("Không thể kết nối đến database")
        return 0, 0
    
    logger.info(f"Đang import {len(articles)} bài viết trực tiếp vào database")
    
    # Kết nối đến database
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()
    
    success_count = 0
    failed_count = 0
    
    try:
        for article in articles:
            try:
                # Chuẩn bị dữ liệu
                title = article.get('title', '')
                slug = article.get('slug', '')
                summary = article.get('summary', '')
                content = article.get('content', '')
                source_name = article.get('source_name', '')
                source_url = article.get('source_url', '')
                source_icon = article.get('source_icon', '')
                published_at = article.get('published_at', datetime.now().isoformat())
                category = article.get('category', 1)  # Đảm bảo sử dụng đúng tên cột trong database
                meta_data = article.get('meta_data', {})
                
                # Đảm bảo meta_data là string JSON
                if isinstance(meta_data, dict):
                    meta_data = json.dumps(meta_data)
                
                # SQL query để insert bài viết
                query = """
                INSERT INTO articles 
                (title, slug, summary, content, source_name, source_url, source_icon, 
                published_at, category, meta_data, is_processed, created_at, updated_at) 
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
                """
                
                # Tạo tuple các giá trị
                values = (
                    title, slug, summary, content, source_name, source_url, source_icon,
                    published_at, category, meta_data, True
                )
                
                # Thực hiện query
                cursor.execute(query, values)
                
                # Đếm số bài viết thành công
                success_count += 1
                logger.info(f"Đã import thành công bài viết: {title}")
                
            except mysql.connector.Error as e:
                # Bỏ qua lỗi duplicate key
                if e.errno == 1062:  # Mã lỗi cho duplicate key
                    logger.warning(f"Bài viết đã tồn tại (slug trùng lặp): {article.get('title', '')}")
                else:
                    logger.error(f"Lỗi MySQL khi import bài viết: {str(e)}")
                
                failed_count += 1
            except Exception as e:
                logger.error(f"Lỗi khi import bài viết: {str(e)}")
                failed_count += 1
        
        # Commit các thay đổi
        conn.commit()
        logger.info(f"Đã import trực tiếp vào database: {success_count} thành công, {failed_count} thất bại")
        
    except Exception as e:
        logger.error(f"Lỗi khi import vào database: {str(e)}")
        logger.error(traceback.format_exc())
        conn.rollback()
    finally:
        cursor.close()
        conn.close()
    
    return success_count, failed_count

def check_db_connection():
    """
    Kiểm tra kết nối đến cơ sở dữ liệu
    
    Returns:
        bool: True nếu kết nối thành công, False nếu thất bại
    """
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        if conn.is_connected():
            logger.info("Kết nối CSDL thành công")
            conn.close()
            return True
        else:
            logger.error("Không thể kết nối CSDL")
            return False
    except Exception as e:
        logger.error(f"Lỗi kết nối CSDL: {str(e)}")
        return False

def check_backend_api():
    """
    Kiểm tra kết nối đến backend API
    
    Returns:
        bool: True nếu kết nối thành công, False nếu thất bại
    """
    try:
        logger.info(f"Kiểm tra kết nối đến Backend API: {CATEGORIES_API_URL}")
        headers = {'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36'}
        response = requests.get(CATEGORIES_API_URL, headers=headers, timeout=10)
        
        if response.status_code == 200:
            categories = response.json()
            logger.info(f"Kết nối API thành công! Tìm thấy {len(categories)} danh mục.")
            
            # In ra 3 danh mục đầu tiên để kiểm tra
            for i, category in enumerate(categories[:3]):
                logger.info(f"Danh mục {i+1}: ID={category.get('id')}, Tên={category.get('name')}")
                
            return True
        else:
            logger.error(f"Lỗi API HTTP {response.status_code}: {response.text}")
            return False
    except Exception as e:
        logger.error(f"Lỗi kết nối API: {str(e)}")
        return False

def generate_slug(title, add_uuid=True):
    """
    Tạo slug từ tiêu đề bài viết
    :param title: Tiêu đề bài viết
    :param add_uuid: Thêm UUID vào slug để đảm bảo duy nhất
    :return: Slug đã được tạo
    """
    # Loại bỏ dấu tiếng Việt
    slug = unidecode(title)
    
    # Chuyển đổi thành chữ thường và thay thế các ký tự không phải chữ cái, số bằng dấu gạch ngang
    slug = re.sub(r'[^\w\s-]', '', slug.lower())
    slug = re.sub(r'[\s_-]+', '-', slug).strip('-')
    
    # Thêm UUID để đảm bảo duy nhất
    if add_uuid:
        unique_id = str(uuid.uuid4())[:8]
        slug = f"{slug}-{unique_id}"
        
    return slug

def main():
    """
    Hàm main xử lý các tham số dòng lệnh và thực thi các lệnh tương ứng
    """
    parser = argparse.ArgumentParser(description="Scraper Service - Tìm kiếm và xử lý tin tức tự động")
    
    parser.add_argument("--all", action="store_true", help="Tìm kiếm và xử lý tất cả các danh mục")
    parser.add_argument("--max", type=int, default=2, help="Số bài viết tối đa cho mỗi danh mục")
    parser.add_argument("--category", type=int, help="ID của danh mục cần xử lý")
    parser.add_argument("--auto-send", action="store_true", help="Tự động gửi bài viết đến backend")
    parser.add_argument("--import", action="store_true", dest="import_all", help="Import tất cả các file JSON vào backend")
    parser.add_argument("--import-file", type=str, help="Path to JSON file to import")
    parser.add_argument("--cleanup", action="store_true", help="Dọn dẹp tệp tin tạm và log cũ")
    parser.add_argument("--check", action="store_true", help="Kiểm tra kết nối database và API")
    
    args = parser.parse_args()
    
    # Kiểm tra kết nối database nếu được yêu cầu
    if args.check:
        print("\n----- KIỂM TRA KẾT NỐI -----")
        db_status = check_db_connection()
        api_status = check_backend_api()
        
        print("\n----- KẾT QUẢ KIỂM TRA -----")
        print(f"Kết nối CSDL: {'✓ Thành công' if db_status else '✗ Thất bại'}")
        print(f"Kết nối API: {'✓ Thành công' if api_status else '✗ Thất bại'}")
        print("---------------------------\n")
        return
    
    # Xử lý tất cả các danh mục
    if args.all:
        print(f"Xử lý tất cả các danh mục, tối đa {args.max} bài viết mỗi danh mục")
        logger.info(f"Bắt đầu tìm và xử lý tất cả các danh mục, tối đa {args.max} bài viết mỗi danh mục")
        
        results = find_and_process_all_categories(max_articles_per_category=args.max)
        
        # Log kết quả thu được
        logger.info(f"Kết quả tìm kiếm: {results['success']} danh mục thành công, {results['failed']} danh mục thất bại")
        logger.info(f"Tổng số bài viết đã tìm được: {results['total_articles']}")
        
        # Tự động gửi bài viết tới backend nếu cờ auto-send được bật
        if args.auto_send and results['all_articles'] and len(results['all_articles']) > 0:
            logger.info(f"Tự động gửi {len(results['all_articles'])} bài viết tới backend")
            
            # Gọi hàm gửi các bài viết tới backend API
            send_status = send_to_backend(results['all_articles'])
            
            if send_status:
                logger.info("Đã gửi tất cả bài viết thành công đến backend")
            else:
                logger.warning("Có lỗi khi gửi bài viết đến backend")
        
        # Lưu tất cả bài viết vào 1 file JSON
        logger.info(f"Bắt đầu lưu tất cả bài viết vào file JSON duy nhất, dữ liệu đầu vào: {type(results)}")
        logger.info(f"Cấu trúc results: {list(results.keys()) if isinstance(results, dict) else type(results)}")
        
        json_file = save_all_articles_to_single_file(results)
        
        if json_file and os.path.exists(json_file):
            logger.info(f"Đã lưu tất cả bài viết vào file JSON: {json_file}")
            
            # Import file JSON vào backend nếu cờ auto-send được bật
            if args.auto_send:
                imported, failed = import_json_file_to_backend(json_file)
                logger.info(f"Đã import file JSON vào backend: {imported} thành công, {failed} thất bại")
        else:
            logger.warning("Không thể lưu bài viết vào file JSON")
            
        logger.info(f"Scraper hoàn thành: Xử lý tất cả các danh mục, tối đa {args.max} bài viết mỗi danh mục")
    
    # Xử lý một danh mục cụ thể
    elif args.category:
        category_id = args.category
        print(f"Xử lý danh mục ID: {category_id}, tối đa {args.max} bài viết")
        
        # Lấy thông tin danh mục từ backend
        try:
            category_info = get_category_by_id(category_id)
            if category_info and 'name' in category_info:
                category_name = category_info['name']
                print(f"Danh mục: {category_name}")
                
                # Xử lý danh mục
                articles = process_category(category_id, category_name, max_articles=args.max)
                
                if articles:
                    print(f"Tìm thấy {len(articles)} bài viết")
                    
                    # Tự động gửi bài viết tới backend nếu cờ auto-send được bật
                    if args.auto_send and len(articles) > 0:
                        send_status = send_to_backend(articles)
                        if send_status:
                            print("Đã gửi tất cả bài viết thành công đến backend")
                        else:
                            print("Có lỗi khi gửi bài viết đến backend")
                else:
                    print("Không tìm thấy bài viết nào phù hợp")
            else:
                print(f"Không tìm thấy thông tin danh mục với ID: {category_id}")
        except Exception as e:
            print(f"Lỗi khi xử lý danh mục {category_id}: {str(e)}")
    
    # Import tất cả các file JSON trong thư mục output
    elif args.import_all:
        print("Import tất cả các file JSON trong thư mục output")
        success, failed = import_all_to_backend()
        print(f"Kết quả: {success} thành công, {failed} thất bại")
    
    # Import file JSON chỉ định
    elif args.import_file:
        json_file = args.import_file
        if not os.path.exists(json_file):
            print(f"File không tồn tại: {json_file}")
            return
        
        print(f"Import file JSON: {json_file}")
        success, failed = import_json_file_to_backend(json_file)
        print(f"Kết quả: {success} thành công, {failed} thất bại")
    
    # Dọn dẹp tệp tin cũ
    elif args.cleanup:
        print("Dọn dẹp các tệp tin tạm và log cũ")
        cleanup_temp_files()
        cleanup_old_files()
    
    # Không có tham số dòng lệnh, hiển thị trợ giúp
    else:
        parser.print_help()

if __name__ == "__main__":
    main() 
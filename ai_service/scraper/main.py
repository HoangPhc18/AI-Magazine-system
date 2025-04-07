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

# Import các module nội bộ
from google_news import (
    fetch_categories_from_backend,
    search_with_category,
    process_all_categories,
    save_article_to_json,
    import_article_to_backend
)
from scrape_articles_selenium import extract_article_content

# Tải biến môi trường từ file .env
load_dotenv()

# Thông tin kết nối database
DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASSWORD = os.getenv('DB_PASSWORD', '')
DB_NAME = os.getenv('DB_NAME', 'AiMagazineDB')

# Config kết nối database
DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "aimagazine",
    "port": 3306
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
logger = logging.getLogger()

# 🔹 Các thông số mặc định
SCRAPER_DIR = os.path.dirname(os.path.abspath(__file__))
TEMP_DIR = os.path.join(SCRAPER_DIR, "temp")
OUTPUT_DIR = os.path.join(SCRAPER_DIR, "output")
DEFAULT_BATCH_SIZE = 5
# Số ngày để giữ lại log files và output files
RETENTION_DAYS = 2

# 🔹 Laravel Backend API URLs
BACKEND_API_URL = "http://localhost:8000/api/articles"
ARTICLES_BATCH_API_URL = "http://localhost:8000/api/articles/batch"
ARTICLES_IMPORT_API_URL = "http://localhost:8000/api/articles/import"
CATEGORIES_API_URL = "http://localhost:8000/api/categories"

# Các biến toàn cục
# Cache lưu các URL đã xử lý để tránh trùng lặp
processed_urls = set()

def check_api_keys():
    """
    Kiểm tra các API keys từ file .env
    """
    api_keys_status = {}
    
    # Kiểm tra WorldNewsAPI
    worldnews_api_key = os.environ.get('WORLDNEWS_API_KEY', '')
    if worldnews_api_key:
        api_keys_status['WorldNewsAPI'] = True
    else:
        api_keys_status['WorldNewsAPI'] = False
        
    # Kiểm tra Currents API
    currents_api_key = os.environ.get('CURRENTS_API_KEY', '')
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
    
    logger.info(f"Gửi {len(normalized_articles)} bài viết tới backend...")
    
    success = True
    total_batches = (len(normalized_articles) + batch_size - 1) // batch_size
    
    for i in range(0, len(normalized_articles), batch_size):
        batch = normalized_articles[i:i+batch_size]
        batch_num = i // batch_size + 1
        
        logger.info(f"Gửi batch {batch_num}/{total_batches} ({len(batch)} bài viết)")
        
        try:
            payload = {"articles": batch}
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json"
            }
            
            # In payload mẫu để kiểm tra
            if batch:
                sample = batch[0].copy()
                if 'content' in sample and sample['content'] and len(sample['content']) > 100:
                    sample['content'] = sample['content'][:100] + "..."
                logger.debug(f"Payload mẫu (bài viết đầu tiên): {json.dumps(sample, ensure_ascii=False)}")
            
            # Thiết lập timeout và số lần thử lại
            max_retries = 3
            retry_count = 0
            
            while retry_count < max_retries:
                try:
                    response = requests.post(
                        BACKEND_API_URL, 
                        json=payload, 
                        headers=headers,
                        timeout=30  # 30 giây timeout
                    )
                    
                    # Kiểm tra xem response có phải JSON không
                    response_content = response.text
                    logger.debug(f"Response status: {response.status_code}")
                    
                    if response.status_code in (200, 201):
                        try:
                            result = response.json()
                            logger.info(f"[OK] Batch {batch_num}: {result.get('message', '')}")
                            if 'errors' in result and result['errors']:
                                logger.warning(f"[WARN] Có {len(result['errors'])} lỗi trong batch {batch_num}:")
                                for error in result['errors']:
                                    logger.warning(f"  - {error}")
                        except json.JSONDecodeError:
                            logger.warning(f"[WARN] Response không phải JSON hợp lệ mặc dù status code = {response.status_code}")
                            logger.warning(f"[WARN] Response content: {response_content[:200]}...")
                        break
                    else:
                        logger.error(f"[ERROR] Batch {batch_num}: Lỗi {response.status_code} - {response.text[:200]}...")
                        retry_count += 1
                        if retry_count < max_retries:
                            wait_time = 2 ** retry_count  # Tăng thời gian chờ theo cấp số nhân
                            logger.info(f"Thử lại sau {wait_time} giây...")
                            time.sleep(wait_time)
                        else:
                            success = False
                            logger.error(f"[ERROR] Đã thử lại {max_retries} lần không thành công cho batch {batch_num}")
                
                except requests.exceptions.Timeout:
                    retry_count += 1
                    if retry_count < max_retries:
                        wait_time = 2 ** retry_count
                        logger.info(f"Request timeout. Thử lại sau {wait_time} giây...")
                        time.sleep(wait_time)
                    else:
                        success = False
                        logger.error(f"[ERROR] Timeout sau {max_retries} lần thử lại cho batch {batch_num}")
                
                except requests.exceptions.ConnectionError:
                    retry_count += 1
                    if retry_count < max_retries:
                        wait_time = 2 ** retry_count
                        logger.info(f"Lỗi kết nối. Thử lại sau {wait_time} giây...")
                        time.sleep(wait_time)
                    else:
                        success = False
                        logger.error(f"[ERROR] Lỗi kết nối sau {max_retries} lần thử lại cho batch {batch_num}")
                
                except Exception as e:
                    logger.error(f"[ERROR] Batch {batch_num}: Lỗi không mong đợi: {str(e)}")
                    success = False
                    break
            
        except Exception as e:
            logger.error(f"[ERROR] Lỗi khi gửi batch {batch_num}: {str(e)}")
            success = False
    
    if success:
        logger.info(f"[OK] Đã gửi thành công {len(normalized_articles)} bài viết tới backend")
    else:
        logger.warning("[WARN] Có lỗi xảy ra khi gửi bài viết tới backend")
    
    return success

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
    Tìm kiếm và xử lý bài viết cho tất cả các danh mục từ backend
    :param max_articles_per_category: Số bài viết tối đa cho mỗi danh mục
    :return: Kết quả tìm kiếm
    """
    logger.info(f"Bắt đầu tìm kiếm và xử lý bài viết cho các danh mục (tối đa {max_articles_per_category} bài/danh mục)")
    
    # Lấy danh sách danh mục từ backend
    categories = fetch_categories_from_backend()
    
    if not categories:
        logger.error("Không thể lấy danh mục từ backend")
        return {"success": 0, "failed": 0, "articles_by_category": {}, "all_articles": [], "total_articles": 0}
    
    logger.info(f"Tìm thấy {len(categories)} danh mục từ backend")
    
    # Kết quả
    results = {
        "success": 0,
        "failed": 0,
        "articles_by_category": {},
        "all_articles": [],
        "total_articles": 0
    }
    
    # Xử lý lần lượt từng danh mục
    for category in categories:
        category_id = category["id"]
        category_name = category["name"]
        
        try:
            articles = process_category(category_id, category_name, max_articles_per_category)
            
            if articles:
                # Cập nhật kết quả thành công
                results["success"] += 1
                results["articles_by_category"][category_name] = articles
                results["all_articles"].extend(articles)
                results["total_articles"] += len(articles)
            else:
                # Không tìm thấy bài viết nào
                results["failed"] += 1
                
        except Exception as e:
            logger.error(f"Lỗi khi xử lý danh mục {category_name}: {str(e)}")
            logger.error(traceback.format_exc())
            results["failed"] += 1
    
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
    output_file = os.path.join(OUTPUT_DIR, f"all_articles_{current_time}_{unique_id}.json")
    
    # Đảm bảo thư mục output tồn tại
    os.makedirs(os.path.dirname(output_file), exist_ok=True)
    
    # Lưu vào file JSON
    try:
        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(all_articles, f, ensure_ascii=False, indent=2)
        
        logger.info(f"Đã lưu {len(all_articles)} bài viết vào file JSON duy nhất: {output_file}")
        
        # In thông tin bài viết đầu tiên để debug
        if all_articles:
            first_article = all_articles[0]
            logger.info(f"Bài viết mẫu: Title={first_article.get('title', 'Không tiêu đề')}, Content length={len(first_article.get('content', ''))}")
            
        return output_file
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
    
    if not os.path.exists(json_file_path):
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
            "category": article.get("category_id", 1)  # Đảm bảo sử dụng category, không phải category_id
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
    Kiểm tra kết nối đến database
    :return: Boolean - True nếu kết nối thành công, False nếu không
    """
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        if conn.is_connected():
            logger.info("Kết nối đến database thành công")
            conn.close()
            return True
        else:
            logger.error("Không thể kết nối đến database")
            return False
    except Exception as e:
        logger.error(f"Lỗi khi kết nối đến database: {str(e)}")
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
    Hàm chính điều khiển chương trình
    """
    parser = argparse.ArgumentParser(description="Tìm kiếm và trích xuất bài viết từ Google News")
    parser.add_argument('--all', action='store_true', help='Xử lý tất cả các danh mục từ backend')
    parser.add_argument('--max-articles', type=int, default=2, help='Số bài viết tối đa cho mỗi danh mục')
    parser.add_argument('--category', type=int, help='ID của danh mục cụ thể để xử lý')
    parser.add_argument('--keyword', type=str, help='Từ khóa tìm kiếm')
    parser.add_argument('--cleanup', action='store_true', help='Dọn dẹp các file tạm và file log cũ')
    parser.add_argument('--retention', type=int, default=RETENTION_DAYS, help='Số ngày giữ lại file (mặc định: 7)')
    parser.add_argument('--import-all', action='store_true', help='Import tất cả file JSON trong thư mục output vào backend')
    parser.add_argument('--no-import', action='store_true', help='Không tự động import bài viết vào backend')
    parser.add_argument('--single-file', action='store_true', help='Lưu tất cả bài viết vào một file JSON duy nhất')
    
    args = parser.parse_args()
    
    try:
        # Kiểm tra và xử lý tham số
        if args.cleanup:
            # Dọn dẹp file tạm và log cũ
            cleanup_temp_files()
            cleanup_old_files(args.retention)
            print(f"Đã dọn dẹp các file tạm và file log cũ hơn {args.retention} ngày")
            return
            
        if args.import_all:
            # Import tất cả file JSON trong thư mục output
            success, failed = import_all_to_backend()
            print(f"Đã import các file JSON: {success} thành công, {failed} thất bại")
            return
            
        # Xử lý tất cả các danh mục
        if args.all:
            print(f"Xử lý tất cả các danh mục, tối đa {args.max_articles} bài viết mỗi danh mục")
            results = find_and_process_all_categories(args.max_articles)
            
            # In kết quả
            total_categories = results['success'] + results['failed']
            total_articles = results['total_articles']
            
            print(f"\nĐã xử lý {total_categories} danh mục")
            print(f"Thành công: {results['success']} danh mục, Thất bại: {results['failed']} danh mục")
            print(f"Tổng số bài viết đã tìm được: {total_articles}")
            
            if results['success'] > 0:
                # In thông tin chi tiết các danh mục thành công
                print("\nCác danh mục đã xử lý thành công:\n")
                
                for category_name, articles in results['articles_by_category'].items():
                    if articles:
                        print(f"- {category_name}")
                        print(f"  Số bài viết: {len(articles)}")
                        
                        for i, article in enumerate(articles, 1):
                            print(f"  Bài viết #{i}:")
                            print(f"    URL: {article['url']}")
                            print(f"    Tiêu đề: {article['title']}")
                            print(f"    Chiều dài nội dung: {article.get('content_length', len(article.get('content', '')))} ký tự")
                            print(f"    Lưu tại: {article.get('json_filepath', article.get('file_path', 'Không rõ'))}")
                            print()
            
            # Lưu tất cả bài viết vào file JSON
            try:
                # Luôn lưu vào một file JSON duy nhất
                json_file_path = save_all_articles_to_single_file(results)
                
                if json_file_path:
                    article_count = 0
                    # Đếm số bài viết đã lưu thực tế
                    if "all_articles" in results and isinstance(results["all_articles"], list):
                        article_count = len(results["all_articles"])
                    else:
                        # Đếm từ tổng số bài viết trong articles_by_category
                        for articles in results['articles_by_category'].values():
                            article_count += len(articles)
                    
                    print(f"\nĐã lưu tất cả {article_count} bài viết vào file: {json_file_path}")
                    
                    if not args.no_import:
                        # Tự động import vào backend
                        print(f"\nĐang import tất cả bài viết vào backend...")
                        success, failed = import_json_file_to_backend(json_file_path)
                        print(f"Đã import vào database: {success} thành công, {failed} thất bại")
            except Exception as e:
                logger.error(f"Lỗi khi lưu hoặc import bài viết: {str(e)}")
                logger.error(traceback.format_exc())
                print(f"Lỗi khi lưu hoặc import bài viết: {str(e)}")
                
            return
        
        # Xử lý theo ID danh mục nếu được chỉ định
        if args.category:
            from google_news import get_category_by_id
            category = get_category_by_id(args.category)
            
            if category:
                articles = process_category(args.category, category['name'], args.max_articles)
                if articles:
                    print(f"Đã xử lý thành công danh mục: {category['name']}")
                    print(f"Tìm thấy {len(articles)} bài viết:")
                    
                    for i, article in enumerate(articles, 1):
                        print(f"\nBài viết #{i}:")
                        print(f"URL: {article['url']}")
                        print(f"Tiêu đề: {article['title']}")
                        print(f"Chiều dài nội dung: {article['content_length']} ký tự")
                        print(f"Lưu tại: {article['json_filepath']}")
                    
                    # Lưu tất cả vào một file JSON duy nhất
                    result = {
                        "categories": [
                            {
                                "id": category["id"],
                                "name": category["name"],
                                "status": "success",
                                "articles": articles
                            }
                        ]
                    }
                    
                    json_file_path = save_all_articles_to_single_file(result)
                    
                    if json_file_path and not args.no_import:
                        # Tự động import vào backend
                        success, failed = import_json_file_to_backend(json_file_path)
                        print(f"Đã import vào database: {success} thành công, {failed} thất bại")
                else:
                    print(f"Không thể xử lý danh mục: {category['name']}")
            else:
                logger.error(f"Không tìm thấy danh mục với ID: {args.category}")
            return
        
        # Xử lý theo từ khóa nếu được chỉ định
        if args.keyword:
            from google_news import search_google_news
            
            logger.info(f"Tìm kiếm với từ khóa: {args.keyword}")
            url = search_google_news(args.keyword)
            
            if url:
                logger.info(f"Đã tìm thấy URL: {url}")
                
                # Trích xuất nội dung
                article_data = extract_article_content(url)
                
                if article_data and article_data.get("title") and article_data.get("content"):
                    # Lưu vào file JSON
                    json_filepath = save_article_to_json(
                        category_id=0,  # 0 vì không có category_id
                        category_name=args.keyword,
                        article_url=url,
                        article_data=article_data
                    )
                    
                    if json_filepath:
                        print(f"Đã xử lý thành công từ khóa: {args.keyword}")
                        print(f"URL: {url}")
                        print(f"Tiêu đề: {article_data['title']}")
                        print(f"Chiều dài nội dung: {len(article_data['content'])} ký tự")
                        print(f"Lưu tại: {json_filepath}")
                        
                        if not args.no_import:
                            # Tự động import vào backend
                            success, failed = import_json_file_to_backend(json_filepath)
                            print(f"Đã import vào database: {success} thành công, {failed} thất bại")
                else:
                    logger.error(f"Không thể trích xuất nội dung từ: {url}")
            else:
                logger.error(f"Không tìm thấy bài viết nào cho từ khóa: {args.keyword}")
            return
        
        # Hiển thị hướng dẫn sử dụng nếu không có lệnh nào được chạy
        parser.print_help()
    
    except Exception as e:
        logger.error(f"Lỗi không xác định trong quá trình xử lý: {str(e)}")
        logger.error(traceback.format_exc())

if __name__ == "__main__":
    main() 
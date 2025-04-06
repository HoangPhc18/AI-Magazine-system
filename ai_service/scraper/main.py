#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Đây là module chính cho scraper bài viết tin tức
Quy trình: 
1. Tìm kiếm URL bài viết từ google_news_serpapi.py 
2. Trích xuất nội dung từ URL bằng scrape_articles_selenium.py
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

# Import các module nội bộ
import google_news_serpapi
import scrape_articles_selenium

# Tải biến môi trường từ file .env
load_dotenv()

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
BACKEND_API_URL = "http://localhost:8000/api/articles/import"
CATEGORIES_API_URL = "http://localhost:8000/api/categories"

# Kiểm tra các API keys
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
        retention_days (int): Số ngày giữ lại files (mặc định: 7)
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

def search_articles():
    """
    Tìm kiếm bài viết từ nhiều nguồn API khác nhau
    
    Returns:
        tuple: (articles, search_file) - Danh sách bài viết và đường dẫn file đã lưu
    """
    logger.info("[STEP 1] Tìm kiếm bài viết từ các nguồn tin tức")
    
    try:
        # Chuẩn bị biến để lưu trữ kết quả
        articles = []
        search_file = None
        
        # Lấy danh sách categories từ API
        categories = google_news_serpapi.get_categories()
        
        if not categories:
            logger.warning("[WARN] Không lấy được danh sách thể loại từ API")
            # Sử dụng danh sách thể loại mặc định
            categories = [
                "Chính trị", "Kinh tế", "Xã hội", "Pháp luật", 
                "Thế giới", "Văn hóa", "Giáo dục", "Y tế", 
                "Khoa học", "Công nghệ", "Thể thao", "Giải trí"
            ]
            logger.info(f"[INFO] Sử dụng danh sách thể loại mặc định: {categories}")
        else:
            logger.info(f"[INFO] Đã lấy được {len(categories)} thể loại từ API")
        
        # Tìm kiếm bài viết cho từng thể loại
        all_articles = []
        
        # Tìm kiếm từ các nguồn khác nhau
        sources = [
            {"name": "Google News", "module": google_news_serpapi}
        ]
        
        # Thêm WorldNewsAPI nếu module tồn tại
        try:
            import worldnews_api
            sources.append({"name": "WorldNewsAPI", "module": worldnews_api})
            logger.info("[INFO] Đã thêm nguồn WorldNewsAPI")
        except ImportError:
            logger.warning("[WARN] Không thể import module worldnews_api")
        
        # Thêm Currents API nếu module tồn tại
        try:
            import currents_api
            sources.append({"name": "Currents API", "module": currents_api})
            logger.info("[INFO] Đã thêm nguồn Currents API")
        except ImportError:
            logger.warning("[WARN] Không thể import module currents_api")
        
        # Hiển thị tổng số nguồn đã kích hoạt
        logger.info(f"[INFO] Tìm kiếm bài viết từ {len(sources)} nguồn: {', '.join([s['name'] for s in sources])}")
        
        # Tìm kiếm bài viết từ mỗi nguồn cho từng danh mục
        for category in categories:
            logger.info(f"\n=== Đang xử lý danh mục: {category} ===")
            
            category_articles = []
            for source in sources:
                source_name = source["name"]
                source_module = source["module"]
                
                try:
                    logger.info(f"[INFO] Tìm kiếm bài viết cho danh mục '{category}' từ nguồn {source_name}")
                    source_articles = source_module.fetch_articles_by_category(category)
                    
                    if source_articles:
                        logger.info(f"[OK] Tìm thấy {len(source_articles)} bài viết cho danh mục '{category}' từ nguồn {source_name}")
                        category_articles.extend(source_articles)
                    else:
                        logger.warning(f"[WARN] Không tìm thấy bài viết nào cho danh mục '{category}' từ nguồn {source_name}")
                
                except Exception as e:
                    logger.error(f"[ERROR] Lỗi khi tìm kiếm bài viết từ nguồn {source_name} cho danh mục '{category}': {str(e)}")
            
            # Hiển thị tổng số bài viết đã tìm thấy cho danh mục
            if category_articles:
                logger.info(f"[OK] Tìm thấy tổng cộng {len(category_articles)} bài viết cho danh mục '{category}'")
                all_articles.extend(category_articles)
            else:
                logger.warning(f"[WARN] Không tìm thấy bài viết nào cho danh mục '{category}' từ tất cả các nguồn")
        
        if all_articles:
            # Xóa các bài viết trùng lặp dựa trên URL
            unique_urls = set()
            articles = []
            
            for article in all_articles:
                url = article.get("source_url", "")
                if url and url not in unique_urls:
                    unique_urls.add(url)
                    articles.append(article)
            
            # Lưu danh sách bài viết vào file
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            search_file = os.path.join(OUTPUT_DIR, f"search_results_{timestamp}.json")
            
            if save_articles_to_file(articles, search_file):
                logger.info(f"[OK] Đã tìm thấy và lưu {len(articles)} bài viết độc nhất vào {search_file}")
            else:
                logger.error("[ERROR] Không thể lưu kết quả tìm kiếm")
        else:
            logger.warning("[WARN] Không tìm thấy bài viết nào từ tất cả các danh mục và nguồn")
        
        return articles, search_file
    
    except Exception as e:
        logger.error(f"[ERROR] Lỗi trong quá trình tìm kiếm bài viết: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
        return [], None

def load_articles_from_file(input_file):
    """
    Đọc dữ liệu bài viết từ file JSON
    
    Args:
        input_file (str): Đường dẫn đến file JSON
        
    Returns:
        tuple: (articles, file_path) - Danh sách bài viết và đường dẫn file
    """
    try:
        logger.info(f"Đọc dữ liệu từ file: {input_file}")
        with open(input_file, "r", encoding="utf-8") as f:
            articles = json.load(f)
        
        if not articles:
            logger.warning(f"Không có bài viết nào trong file {input_file}")
            return [], input_file
        
        # Chuẩn hóa dữ liệu nếu cần
        for article in articles:
            # Đảm bảo meta_data là đối tượng Python
            if isinstance(article.get("meta_data"), str):
                try:
                    article["meta_data"] = json.loads(article["meta_data"])
                except json.JSONDecodeError:
                    article["meta_data"] = {}
        
        logger.info(f"Đã đọc {len(articles)} bài viết từ {input_file}")
        return articles, input_file
    except Exception as e:
        logger.error(f"Lỗi khi đọc file {input_file}: {str(e)}")
        return [], input_file

def send_articles_to_backend(articles, batch_size=DEFAULT_BATCH_SIZE, auto_send=True):
    """
    Gửi bài viết tới backend API
    
    Args:
        articles (list): Danh sách bài viết
        batch_size (int): Số lượng bài viết gửi trong mỗi request
        auto_send (bool): Tự động gửi không cần xác nhận
    
    Returns:
        bool: Trạng thái thành công
    """
    return send_to_backend(articles, batch_size, auto_send)

def extract_content_from_articles(articles):
    """
    Trích xuất nội dung từ URL bài viết sử dụng scrape_articles_selenium.py
    
    Args:
        articles (list): Danh sách bài viết với URL
        
    Returns:
        list: Danh sách bài viết đã trích xuất nội dung
    """
    if not articles:
        logger.warning("Không có bài viết nào để trích xuất nội dung!")
        return []
    
    logger.info(f"[STEP 2] Trích xuất nội dung cho {len(articles)} bài viết")
    
    # Đảm bảo thư mục output tồn tại
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    # Thiết lập driver Selenium
    try:
        driver = scrape_articles_selenium.setup_driver()
    except Exception as e:
        logger.error(f"[ERROR] Không thể thiết lập Selenium driver: {str(e)}")
        logger.error("Vui lòng kiểm tra cài đặt ChromeDriver và thử lại.")
        return []
    
    try:
        enriched_articles = []
        errors = 0
        
        for i, article in enumerate(articles, 1):
            title = article.get("title", "Unknown title")
            url = article.get("source_url", "")
            
            if not url:
                logger.warning(f"Bài viết {i} không có URL: {title}")
                continue
            
            # Kiểm tra URL phù hợp
            if not scrape_articles_selenium.filter_article(url):
                logger.info(f"[INFO] Bỏ qua URL không phù hợp: {url}")
                continue
            
            logger.info(f"[INFO] Đang trích xuất nội dung cho bài viết {i}/{len(articles)}: {title}")
            
            try:
                # Sử dụng hàm enrich_article từ scrape_articles_selenium
                enriched = scrape_articles_selenium.enrich_article(driver, article)
                if enriched:
                    # Kiểm tra xem bài viết có nội dung trích xuất không
                    if enriched.get("content") and len(enriched.get("content", "").strip()) > 100:
                        logger.info(f"[OK] Đã trích xuất thành công nội dung ({len(enriched.get('content', '').split())} từ)")
                        enriched_articles.append(enriched)
                    else:
                        logger.warning(f"[WARN] Bài viết có nội dung quá ngắn hoặc trống: {url}")
                        # Vẫn thêm vào danh sách nếu có title
                        if enriched.get("title") and enriched.get("title") != "Unknown title":
                            enriched_articles.append(enriched)
            except Exception as e:
                logger.error(f"[ERROR] Lỗi khi trích xuất nội dung cho URL {url}: {str(e)}")
                errors += 1
            
            # Thêm độ trễ giữa các request để tránh tải quá mức
            if i < len(articles):
                time.sleep(2)
                
                # Lưu bài viết đã được làm giàu
                timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        enriched_file = os.path.join(OUTPUT_DIR, f"enriched_articles_{timestamp}.json")
        save_articles_to_file(enriched_articles, enriched_file)
        
        logger.info(f"[OK] Đã trích xuất nội dung cho {len(enriched_articles)} bài viết (bỏ qua {errors} lỗi)")
        return enriched_articles
    
    except Exception as e:
        logger.error(f"[ERROR] Lỗi trong quá trình trích xuất nội dung: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
        return []
    finally:
        # Đóng driver khi hoàn thành
        try:
            driver.quit()
        except:
            pass

def main():
    """
    Hàm chính kiểm soát quy trình scraping
    """
    global OUTPUT_DIR  # Khai báo global đặt ở đầu hàm

    try:
        parser = argparse.ArgumentParser(description='Scraper cho tin tức và bài viết.')
        parser.add_argument('--skip-search', action='store_true', help='Bỏ qua bước tìm kiếm URL bài viết')
        parser.add_argument('--skip-extraction', action='store_true', help='Bỏ qua bước trích xuất nội dung')
        parser.add_argument('--skip-send', action='store_true', help='Bỏ qua bước gửi đến backend')
        parser.add_argument('--input-file', help='File JSON chứa bài viết để xử lý')
        parser.add_argument('--auto-send', action='store_true', help='Tự động gửi bài viết không cần xác nhận')
        parser.add_argument('--batch-size', type=int, default=5, help='Số lượng bài viết gửi trong mỗi request')
        parser.add_argument('--verbose', action='store_true', help='Hiển thị nhiều thông tin hơn')
        parser.add_argument('--retention-days', type=int, default=2, help='Số ngày giữ lại files trước khi xóa')
        parser.add_argument('--no-cleanup', action='store_true', help='Không xóa files cũ')
        parser.add_argument('--output-dir', default=OUTPUT_DIR, help='Thư mục lưu kết quả')
        args = parser.parse_args()
        
        # Điều chỉnh mức log nếu verbose
        if args.verbose:
            logger.setLevel(logging.DEBUG)
            logger.debug("Chế độ verbose được bật")
        
        # Cập nhật thư mục output nếu cần
        if args.output_dir != OUTPUT_DIR:
            OUTPUT_DIR = args.output_dir
            logger.info(f"Thư mục output đã được cập nhật: {OUTPUT_DIR}")
        
        # Tạo thư mục output và temp nếu chưa tồn tại
        os.makedirs(OUTPUT_DIR, exist_ok=True)
        os.makedirs(TEMP_DIR, exist_ok=True)
        
        logger.info(f"Lưu trữ kết quả vào thư mục: {OUTPUT_DIR}")
        
        # Dọn dẹp files cũ nếu cần
        if not args.no_cleanup:
            cleanup_old_files(args.retention_days)
            
        # Kiểm tra API keys từ file .env
        api_keys = check_api_keys()
        
        # Tìm kiếm bài viết mới
        articles = []
        search_file = None
        
        if not args.skip_search:
            articles, search_file = search_articles()
        elif args.input_file:
            # Sử dụng file input đã chỉ định
            if os.path.exists(args.input_file):
                articles, search_file = load_articles_from_file(args.input_file)
                logger.info(f"[OK] Đã tải {len(articles)} bài viết từ {args.input_file}")
            else:
                logger.error(f"[ERROR] Không tìm thấy file: {args.input_file}")
                return
        else:
            # Tìm file JSON gần nhất trong thư mục output
            search_files = sorted(glob.glob(os.path.join(OUTPUT_DIR, "search_results_*.json")), reverse=True)
            
            if search_files:
                search_file = search_files[0]
                articles, _ = load_articles_from_file(search_file)
                logger.info(f"[INFO] Sử dụng file tìm kiếm gần nhất: {search_file}")
                logger.info(f"[OK] Đã tải {len(articles)} bài viết từ {search_file}")
            else:
                logger.error("[ERROR] Không tìm thấy file tìm kiếm nào trong thư mục output")
                logger.error("[ERROR] Vui lòng chạy lại mà không có --skip-search hoặc cung cấp --input-file")
                return
        
        # Trích xuất nội dung cho các bài viết
        if not args.skip_extraction and articles:
            extraction_results = extract_content_from_articles(articles)
            
            # Gửi bài viết đến backend
            if extraction_results and not args.skip_send:
                send_articles_to_backend(extraction_results, args.batch_size, args.auto_send)
        elif not args.skip_send and articles:
            # Kiểm tra nếu các bài viết đã có nội dung
            articles_with_content = [a for a in articles if a.get('content')]
            
            if articles_with_content:
                send_articles_to_backend(articles_with_content, args.batch_size, args.auto_send)
            else:
                logger.warning("[WARN] Không có bài viết nào có nội dung để gửi")
                logger.info("[INFO] Vui lòng chạy lại mà không có --skip-extraction để trích xuất nội dung trước khi gửi")
        
        logger.info(f"[OK] Hoàn thành quá trình xử lý. Kết quả nằm trong thư mục: {OUTPUT_DIR}")
                
    except Exception as e:
        logger.error(f"[ERROR] Lỗi không xác định trong quá trình xử lý: {str(e)}")
        logger.error(traceback.format_exc())

if __name__ == "__main__":
    main() 
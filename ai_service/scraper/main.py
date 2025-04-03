#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Đây là module chính cho scraper bài viết tin tức
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

# Import các module nội bộ
import google_news_serpapi
from google_news_serpapi import fetch_articles_by_category, get_categories
import scrape_articles_selenium

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

def extract_content(articles, extraction_method="javascript"):
    """
    Trích xuất nội dung bài viết từ URLs sử dụng Selenium
    
    Args:
        articles (list): Danh sách các bài viết (dicts)
        extraction_method (str): Phương thức trích xuất (javascript/readability)
        
    Returns:
        list: Danh sách bài viết đã được bổ sung nội dung
    """
    if not articles:
        logger.warning("Không có bài viết nào để trích xuất nội dung!")
        return []
    
    # Thiết lập driver Selenium
    driver = scrape_articles_selenium.setup_driver()
    
    try:
        enriched_articles = []
        
        for i, article in enumerate(articles, 1):
            title = article.get("title", "")
            url = article.get("source_url", "")
            
            if not url:
                logger.warning(f"Bài viết {i} không có URL: {title}")
                continue
            
            # Kiểm tra URL phù hợp
            if "vtv.vn/video/" in url:
                logger.info(f"[INFO] Bỏ qua bài viết video: {title} - {url}")
                continue
            
            logger.info(f"[INFO] Đang trích xuất nội dung cho bài viết {i}/{len(articles)}: {title}")
            
            try:
                # Sử dụng Selenium để trích xuất nội dung
                content = scrape_articles_selenium.extract_content(driver, url, title)
                
                # Cập nhật bài viết với nội dung đã trích xuất
                enriched_article = article.copy()
                enriched_article["content"] = content
                
                # Cập nhật meta_data
                if isinstance(enriched_article.get("meta_data"), dict):
                    enriched_article["meta_data"]["extracted_at"] = datetime.now().isoformat()
                    enriched_article["meta_data"]["word_count"] = len(content.split())
                else:
                    # Tạo metadata nếu chưa có
                    enriched_article["meta_data"] = {
                        "extracted_at": datetime.now().isoformat(),
                        "word_count": len(content.split())
                    }
                
                # Đảm bảo source_name là chuỗi
                if isinstance(enriched_article.get("source_name"), dict):
                    enriched_article["source_name"] = enriched_article["source_name"].get("name", "Unknown Source")
                
                enriched_articles.append(enriched_article)
                logger.info(f"[OK] Đã trích xuất nội dung cho bài viết {i}/{len(articles)}: {title}")
                
                # Thêm độ trễ giữa các request để tránh tải quá mức
                if i < len(articles):
                    time.sleep(2)
                
            except Exception as e:
                logger.error(f"[ERROR] Lỗi khi trích xuất {url}: {str(e)}")
                # Vẫn thêm bài viết vào danh sách kể cả khi lỗi, nhưng với nội dung rỗng
                article["content"] = f"Lỗi trích xuất: {str(e)}"
                enriched_articles.append(article)
    
    finally:
        # Đóng driver khi hoàn thành
        driver.quit()
    
    return enriched_articles

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
        auto_send (bool): Tự động gửi không cần xác nhận
        
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
    
    # Tự động gửi mà không cần hỏi
    logger.info(f"Tự động gửi {len(normalized_articles)} bài viết tới backend")
    
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
            
            # In payload để kiểm tra
            logger.debug(f"Payload mẫu (bài viết đầu tiên): {json.dumps(batch[0], ensure_ascii=False)[:200]}...")
            
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
                    logger.debug(f"Response raw: {response_content[:200]}...")
                    
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
                        logger.error(f"[ERROR] Batch {batch_num}: Lỗi {response.status_code} - {response.text}")
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

def cleanup_old_files(days=RETENTION_DAYS):
    """
    Dọn dẹp các file cũ (logs, outputs) để tránh tốn dung lượng
    
    Args:
        days (int): Số ngày để giữ lại file
    """
    # Thời điểm hiện tại
    now = datetime.now()
    cutoff_date = now - timedelta(days=days)
    
    # Dọn dẹp output files
    logger.info(f"Dọn dẹp files đầu ra cũ hơn {days} ngày...")
    cleaned_count = 0
    
    if os.path.exists(OUTPUT_DIR):
        for file_name in os.listdir(OUTPUT_DIR):
            file_path = os.path.join(OUTPUT_DIR, file_name)
            if os.path.isfile(file_path):
                # Lấy thời gian sửa đổi
                mtime = os.path.getmtime(file_path)
                file_date = datetime.fromtimestamp(mtime)
                
                # Nếu file cũ hơn số ngày quy định, xóa nó
                if file_date < cutoff_date:
                    try:
                        os.remove(file_path)
                        cleaned_count += 1
                        logger.debug(f"Đã xóa file cũ: {file_path}")
                    except Exception as e:
                        logger.error(f"Không thể xóa file {file_path}: {e}")
    
    # Dọn dẹp log files
    log_files = [f for f in os.listdir(SCRAPER_DIR) if f.startswith("scraper_") and f.endswith(".log")]
    for log_file in log_files:
        # Trích xuất ngày từ tên file (định dạng scraper_YYYYMMDD.log)
        try:
            date_str = log_file.replace("scraper_", "").replace(".log", "")
            file_date = datetime.strptime(date_str, "%Y%m%d")
            
            # Nếu file cũ hơn số ngày quy định, xóa nó
            if file_date < cutoff_date:
                file_path = os.path.join(SCRAPER_DIR, log_file)
                try:
                    os.remove(file_path)
                    cleaned_count += 1
                    logger.debug(f"Đã xóa log file cũ: {log_file}")
                except Exception as e:
                    logger.error(f"Không thể xóa file {file_path}: {e}")
        except (ValueError, IndexError):
            continue
    
    logger.info(f"[OK] Đã dọn dẹp {cleaned_count} files cũ")

def main():
    """
    Hàm chính điều phối quy trình scraping
    """
    parser = argparse.ArgumentParser(description="Scraper bài viết tin tức")
    parser.add_argument("--skip-search", action="store_true", help="Bỏ qua bước tìm kiếm bài viết")
    parser.add_argument("--skip-extraction", action="store_true", help="Bỏ qua bước trích xuất nội dung")
    parser.add_argument("--skip-send", action="store_true", help="Bỏ qua bước gửi đến backend")
    parser.add_argument("--input-file", type=str, help="File JSON chứa bài viết để xử lý")
    parser.add_argument("--auto-send", action="store_true", help="Tự động gửi bài viết không cần xác nhận")
    parser.add_argument("--batch-size", type=int, default=DEFAULT_BATCH_SIZE, help="Số lượng bài viết gửi trong mỗi request")
    parser.add_argument("--verbose", action="store_true", help="Hiển thị nhiều thông tin hơn")
    parser.add_argument("--retention-days", type=int, default=RETENTION_DAYS, help="Số ngày giữ lại files trước khi xóa")
    parser.add_argument("--no-cleanup", action="store_true", help="Không xóa files cũ")
    
    args = parser.parse_args()
    
    # Thiết lập mức độ chi tiết logging
    if args.verbose:
        logger.setLevel(logging.DEBUG)
    
    # Tạo thư mục làm việc nếu chưa tồn tại
    os.makedirs(TEMP_DIR, exist_ok=True)
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    # Dọn dẹp files cũ nếu không có cờ --no-cleanup
    if not args.no_cleanup:
        cleanup_old_files(args.retention_days)
    
    articles = []
    
    try:
        # BƯỚC 1: Tìm kiếm bài viết từ danh mục
        if not args.skip_search and not args.input_file:
            logger.info("[STEP 1] Tìm kiếm bài viết từ danh mục")
            
            # Lấy danh sách danh mục từ backend
            categories = get_categories()
            
            # Tìm kiếm bài viết cho mỗi danh mục
            for category in categories:
                category_articles = fetch_articles_by_category(category)
                articles.extend(category_articles)
            
            logger.info(f"[OK] Đã tìm thấy tổng cộng {len(articles)} bài viết")
            
            # Lưu trữ kết quả tìm kiếm
            if articles:
                timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
                output_file = os.path.join(OUTPUT_DIR, f"search_results_{timestamp}.json")
                with open(output_file, "w", encoding="utf-8") as f:
                    json.dump(articles, f, ensure_ascii=False, indent=4)
                logger.info(f"[OK] Đã lưu kết quả tìm kiếm vào {output_file}")
        
        # Nếu có input_file, đọc từ file
        elif args.input_file:
            try:
                with open(args.input_file, "r", encoding="utf-8") as f:
                    articles = json.load(f)
                logger.info(f"[OK] Đã đọc {len(articles)} bài viết từ {args.input_file}")
            except Exception as e:
                logger.error(f"[ERROR] Lỗi khi đọc file {args.input_file}: {str(e)}")
                return
        
        # BƯỚC 2: Trích xuất nội dung bài viết
        if not args.skip_extraction and articles:
            # Kiểm tra nếu bài viết chưa có nội dung
            articles_without_content = [a for a in articles if not a.get("content")]
            
            if articles_without_content:
                logger.info(f"[STEP 2] Trích xuất nội dung cho {len(articles_without_content)} bài viết")
                enriched_articles = extract_content(articles_without_content)
                
                # Cập nhật danh sách bài viết gốc với nội dung đã trích xuất
                articles_with_content = [a for a in articles if a.get("content")]
                articles = articles_with_content + enriched_articles
                
                # Lưu bài viết đã được làm giàu
                timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
                enriched_output_file = os.path.join(OUTPUT_DIR, f"enriched_articles_{timestamp}.json")
                save_articles_to_file(articles, enriched_output_file)
            else:
                logger.info("[STEP 2] Tất cả bài viết đã có nội dung, bỏ qua bước trích xuất")
        
        # BƯỚC 3: Gửi bài viết đến backend
        if not args.skip_send and articles:
            logger.info(f"[STEP 3] Gửi {len(articles)} bài viết tới backend")
            
            # Áp dụng tập tin enrich_json.py để chuẩn hóa dữ liệu
            import enrich_json
            
            # Lưu dữ liệu vào tệp tạm thời
            temp_file = os.path.join(TEMP_DIR, f"temp_articles_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json")
            with open(temp_file, "w", encoding="utf-8") as f:
                json.dump(articles, f, ensure_ascii=False, indent=4)
                
            # Sử dụng function từ enrich_json để chuẩn hóa
            api_ready_file = enrich_json.fix_json_for_api(temp_file)
            
            # Đọc dữ liệu đã chuẩn hóa
            with open(api_ready_file, "r", encoding="utf-8") as f:
                api_ready_articles = json.load(f)
                
            # Gửi dữ liệu đã chuẩn hóa - luôn tự động gửi
            success = send_to_backend(api_ready_articles, args.batch_size, True)
            
            if success:
                logger.info("[OK] Đã hoàn thành quy trình scraping!")
            else:
                logger.warning("[WARN] Quá trình gửi bài viết tới backend không hoàn chỉnh")
                
    except KeyboardInterrupt:
        logger.info("[INFO] Quá trình bị dừng bởi người dùng")
    except Exception as e:
        logger.error(f"[ERROR] Lỗi không mong đợi: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
    finally:
        # Dọn dẹp các file tạm thời
        cleanup_temp_files()
        logger.info("[INFO] Kết thúc chương trình")

if __name__ == "__main__":
    main() 
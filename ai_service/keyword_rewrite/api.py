#!/usr/bin/env python3
# -*- coding: utf-8 -*-


import os
import sys
import logging
import time
import json
import threading
import requests
from datetime import datetime
import urllib.parse
import google.generativeai as genai

# Vô hiệu hóa tự động tải .env của Flask
os.environ["FLASK_SKIP_DOTENV"] = "1"

from flask import Flask, request, jsonify
from flask_cors import CORS

# Import module config
from config import get_config, reload_config

# Import our modules
from search import search_google_news
from scraper import extract_article_content
from rewriter import rewrite_content

# Tải cấu hình
config = get_config()

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(f"keyword_rewrite_api_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger()

# Initialize Flask app
app = Flask(__name__)
CORS(app)

# Get model configuration from environment
GEMINI_MODEL = config.get("GEMINI_MODEL", "gemini-1.5-flash-latest")
GEMINI_API_KEY = config.get("GEMINI_API_KEY", "")

# Initialize Gemini API
genai.configure(api_key=GEMINI_API_KEY)

# Biến toàn cục để theo dõi các yêu cầu đang xử lý
active_tasks = {}

def process_keyword_task(keyword, rewrite_id, callback_url):
    """
    Process a keyword rewrite task asynchronously.
    
    Args:
        keyword (str): The search keyword
        rewrite_id (int): The ID of the rewrite record in the database
        callback_url (str): URL to send results back to
    """
    logger.info(f"Starting keyword rewrite task for: {keyword} (ID: {rewrite_id})")
    
    # Sửa callback URL - bỏ admin
    callback_url = callback_url.replace('/api/admin/keyword-rewrites/callback', '/api/keyword-rewrites/callback')
    logger.info(f"Modified callback URL: {callback_url}")
    
    try:
        # Số lượng bài viết cần tạo
        num_articles = 3
        
        # Mảng lưu kết quả của các bài viết
        articles = []
        
        # Bắt đầu tìm kiếm và xử lý 3 bài viết khác nhau
        for article_index in range(num_articles):
            logger.info(f"Processing article {article_index + 1}/{num_articles} for keyword: {keyword}")
            
            # Step 1: Search Google News for the keyword, skip previous results
            logger.info(f"Searching Google News for: {keyword}, skipping first {article_index} results")
            article_url = search_google_news(keyword, skip=article_index)
            
            if not article_url:
                logger.error(f"No article found for keyword: {keyword} (article index: {article_index})")
                # Add failure information for this article
                articles.append({
                    "index": article_index,
                    "status": "failed",
                    "error_message": f"Không tìm thấy bài viết thứ {article_index + 1} cho từ khóa đã cho. Hệ thống đã lọc ra các trang không thể trích xuất nội dung như laodong.vn và baomoi.com."
                })
                continue
                
            logger.info(f"Found article URL: {article_url}")
            
            # Thử tìm bài viết tối đa 3 lần
            max_retries = 3
            article_data = None
            
            for attempt in range(1, max_retries + 1):
                # Step 2: Extract content from the URL
                logger.info(f"Extracting content from URL (attempt {attempt}/{max_retries}): {article_url}")
                article_data = extract_article_content(article_url)
                
                # Kiểm tra xem article_data có phải là None không
                if article_data is None:
                    logger.error(f"Failed to extract content from {article_url} - article_data is None")
                    if attempt < max_retries:
                        logger.warning(f"Failed to extract content on attempt {attempt}, trying another URL")
                        article_url = search_google_news(keyword, skip=article_index + attempt)
                        if not article_url:
                            logger.error(f"No more articles found for keyword: {keyword}")
                            break
                        continue
                    else:
                        # Add failure information for this article
                        articles.append({
                            "index": article_index,
                            "status": "failed",
                            "source_url": article_url,
                            "error_message": f"Không thể trích xuất nội dung từ URL bài viết sau {max_retries} lần thử."
                        })
                        break
                
                # Bây giờ chúng ta biết article_data không phải là None, an toàn để truy cập các thuộc tính của nó
                if article_data.get("title") and article_data.get("content") and len(article_data.get("content", "")) > 200:
                    logger.info(f"Successfully extracted content - Title: {article_data['title']}, Content length: {len(article_data['content'])}")
                    break
                    
                # Log chi tiết hơn về vấn đề trích xuất
                if not article_data.get("title"):
                    logger.warning(f"Failed to extract title from {article_url}")
                elif not article_data.get("content"):
                    logger.warning(f"Failed to extract content from {article_url}")
                elif len(article_data.get("content", "")) <= 200:
                    logger.warning(f"Extracted content too short ({len(article_data.get('content', ''))} chars) from {article_url}")
                
                if attempt < max_retries:
                    logger.warning(f"Failed to extract content on attempt {attempt}, trying another URL")
                    # Tìm URL khác
                    article_url = search_google_news(keyword, skip=article_index + attempt)
                    if not article_url:
                        logger.error(f"No more articles found for keyword: {keyword}")
                        # Add failure information for this article
                        articles.append({
                            "index": article_index,
                            "status": "failed",
                            "error_message": f"Không tìm thấy bài viết thay thế sau {attempt} lần thử."
                        })
                        break
            
            # Kiểm tra lại sau khi thử tối đa số lần
            if (not article_data or 
                not article_data.get("title") or 
                not article_data.get("content") or 
                len(article_data.get("content", "")) < 200):
                
                logger.error(f"Failed to extract content from URL after {max_retries} attempts")
                if not articles or len(articles) <= article_index or articles[article_index]["status"] != "failed":
                    error_detail = "Không thể trích xuất "
                    
                    if not article_data:
                        error_detail += "dữ liệu bài viết"
                    else:
                        if not article_data.get("title"):
                            error_detail += "tiêu đề"
                        if not article_data.get("content"):
                            error_detail += " và nội dung" if not article_data.get("title") else "nội dung"
                        elif len(article_data.get("content", "")) < 200:
                            error_detail += "đủ nội dung (nội dung quá ngắn)"
                    
                    articles.append({
                        "index": article_index,
                        "status": "failed",
                        "source_url": article_url,
                        "error_message": f"{error_detail} từ URL bài viết sau {max_retries} lần thử."
                    })
                continue
                
            # Step 3: Rewrite the content using Gemini
            logger.info(f"Rewriting content using Gemini (model: {GEMINI_MODEL}) for article {article_index + 1}")
            rewritten_content = rewrite_content(article_data["title"], article_data["content"])
            
            if rewritten_content.startswith("Error:"):
                logger.error(f"Error rewriting content: {rewritten_content}")
                articles.append({
                    "index": article_index,
                    "status": "failed",
                    "source_url": article_url,
                    "source_title": article_data["title"],
                    "source_content": article_data["content"],
                    "error_message": rewritten_content
                })
                continue
                
            logger.info(f"Successfully rewrote content for article {article_index + 1}. Rewritten length: {len(rewritten_content)}")
            
            # Add successful article information
            articles.append({
                "index": article_index,
                "status": "completed",
                "source_url": article_url,
                "source_title": article_data["title"],
                "source_content": article_data["content"],
                "rewritten_content": rewritten_content
            })
        
        # Check if we have at least one successful article
        successful_articles = [article for article in articles if article["status"] == "completed"]
        
        # Step 4: Send back the results
        if len(successful_articles) > 0:
            # At least one article was successfully processed
            logger.info(f"Completed keyword rewrite task for: {keyword} (ID: {rewrite_id}) - {len(successful_articles)}/{num_articles} articles created")
            
            # Use the first successful article as the primary result, but send all articles
            primary_article = successful_articles[0]
            send_callback(callback_url, rewrite_id, "completed", 
                         source_url=primary_article["source_url"],
                         source_title=primary_article["source_title"],
                         source_content=primary_article["source_content"],
                         rewritten_content=primary_article["rewritten_content"],
                         all_articles=articles)
        else:
            # No articles were successfully processed
            logger.error(f"Failed to process any articles for keyword: {keyword}")
            error_messages = [article.get("error_message", "Unknown error") for article in articles]
            send_callback(callback_url, rewrite_id, "failed", 
                         error_message=f"Không thể tạo bài viết nào từ từ khóa '{keyword}'. Chi tiết lỗi: " + " | ".join(error_messages))
        
        logger.info(f"Keyword rewrite task completed for: {keyword} (ID: {rewrite_id})")
        
    except Exception as e:
        logger.error(f"Error processing keyword task: {str(e)}")
        send_callback(callback_url, rewrite_id, "failed", error_message=f"Processing error: {str(e)}")

def send_callback(callback_url, rewrite_id, status, source_url=None, source_title=None, 
               source_content=None, rewritten_content=None, error_message=None, all_articles=None):
    """
    Gửi kết quả xử lý về cho backend thông qua callback URL
    
    Args:
        callback_url (str): URL to send results back to
        rewrite_id (int): ID của yêu cầu rewrite
        status (str): Trạng thái mới (completed, failed)
        source_url (str, optional): URL nguồn
        source_title (str, optional): Tiêu đề nguồn
        source_content (str, optional): Nội dung nguồn
        rewritten_content (str, optional): Nội dung đã viết lại
        error_message (str, optional): Thông báo lỗi nếu có
        all_articles (list, optional): Danh sách tất cả các bài viết đã xử lý
    """
    try:
        payload = {
            'rewrite_id': rewrite_id,
            'status': status
        }
        
        # Add optional fields if they exist
        if source_url:
            payload['source_url'] = source_url
        if source_title:
            payload['source_title'] = source_title
        if source_content:
            payload['source_content'] = source_content
        if rewritten_content:
            payload['rewritten_content'] = rewritten_content
        if error_message:
            payload['error_message'] = error_message
        if all_articles:
            payload['all_articles'] = all_articles
        
        # Sửa URL callback dựa trên môi trường
        if os.path.exists('/.dockerenv'):
            # Lấy cấu hình từ config
            backend_url = config.get("BACKEND_URL", "http://localhost")
            
            # Phát hiện hệ điều hành bằng cách kiểm tra nhiều dấu hiệu
            # Method 1: Kiểm tra platform
            is_linux = 'linux' in sys.platform.lower()
            
            # Method 2: Kiểm tra file /etc/os-release
            if os.path.exists('/etc/os-release'):
                try:
                    with open('/etc/os-release', 'r') as f:
                        if 'Linux' in f.read():
                            is_linux = True
                except:
                    pass
            
            # Method 3: Kiểm tra đường dẫn Docker
            if os.path.exists('/var/run/docker.sock'):
                is_linux = True
                
            # Method 4: Kiểm tra kết nối được với host.docker.internal hay không
            try:
                # Thử kết nối đến host.docker.internal với timeout ngắn
                test_response = requests.get("http://host.docker.internal", timeout=0.5)
                is_linux = False  # Nếu kết nối được, có thể là Windows
            except:
                # Không kết nối được, có thể là Linux
                # Nhưng không đủ để kết luận, giữ nguyên is_linux
                pass
                
            # Method 5: Kiểm tra kết nối được với 172.17.0.1 hay không
            try:
                # Thử kết nối đến 172.17.0.1 với timeout ngắn
                test_response = requests.get("http://172.17.0.1", timeout=0.5)
                is_linux = True  # Nếu kết nối được, có thể là Linux
            except:
                # Không kết nối được, không đủ để kết luận
                pass
                
            # Phân tích URL callback
            parsed_url = urllib.parse.urlparse(callback_url)
            hostname = parsed_url.netloc.split(':')[0]
            
            # Ghi nhận thông tin phát hiện môi trường
            logger.info(f"Phát hiện môi trường: {'Linux' if is_linux else 'Windows'}")
            
            if hostname in ['localhost', '127.0.0.1']:
                # Chọn host IP phù hợp theo nền tảng
                if is_linux:
                    new_hostname = '172.17.0.1'
                else:
                    new_hostname = 'host.docker.internal'
                
                # Thay thế hostname và đảm bảo port 80
                new_netloc = parsed_url.netloc.replace(hostname, new_hostname)
                if ':8000' in new_netloc:
                    new_netloc = new_netloc.replace(':8000', '')
                    
                parsed_url = parsed_url._replace(netloc=new_netloc)
                fixed_callback_url = urllib.parse.urlunparse(parsed_url)
                
                logger.info(f"Đã điều chỉnh URL callback từ {callback_url} thành {fixed_callback_url}")
            else:
                # Kiểm tra xem URL đã có chứa host.docker.internal mà container không kết nối được
                if 'host.docker.internal' in callback_url and is_linux:
                    # Thay host.docker.internal bằng 172.17.0.1 cho Linux
                    fixed_callback_url = callback_url.replace('host.docker.internal', '172.17.0.1')
                    logger.info(f"Đã điều chỉnh URL host.docker.internal thành 172.17.0.1: {fixed_callback_url}")
                # Kiểm tra xem URL đã có chứa 172.17.0.1 mà container không kết nối được
                elif '172.17.0.1' in callback_url and not is_linux:
                    # Thay 172.17.0.1 bằng host.docker.internal cho Windows
                    fixed_callback_url = callback_url.replace('172.17.0.1', 'host.docker.internal')
                    logger.info(f"Đã điều chỉnh URL 172.17.0.1 thành host.docker.internal: {fixed_callback_url}")
                else:
                    # Giữ URL nguyên bản nếu không cần điều chỉnh
                    fixed_callback_url = callback_url
                
            # Chuẩn bị headers
            headers = {
                'Host': 'magazine.test',
                'Content-Type': 'application/json'
            }
            
            logger.info(f"Sending callback with Host: magazine.test to: {fixed_callback_url}")
            response = requests.post(fixed_callback_url, json=payload, headers=headers, timeout=30)
        else:
            # Không chạy trong Docker, sử dụng callback_url nguyên bản
            fixed_callback_url = callback_url
            logger.info(f"Sending callback to: {fixed_callback_url}")
            response = requests.post(fixed_callback_url, json=payload, timeout=30)
        
        if response.status_code >= 200 and response.status_code < 300:
            logger.info(f"Callback successfully sent, status code: {response.status_code}")
        else:
            logger.error(f"Error sending callback: {response.status_code} - {response.text}")
    except Exception as e:
        # Log any errors that occur during callback
        logger.error(f"Error sending callback: {str(e)}")

@app.route('/api/keyword_rewrite/process', methods=['POST'])
def process_keyword():
    """
    API endpoint to process keyword and retrieve relevant content.
    
    Expected JSON body:
    {
        "keyword": "search term",
        "rewrite_id": 123,
        "callback_url": "https://example.com/api/callback"
    }
    """
    try:
        data = request.json

        # Validate required fields
        if not all(key in data for key in ['keyword', 'rewrite_id', 'callback_url']):
            return jsonify({
                'status': 'error',
                'message': 'Missing required fields: keyword, rewrite_id, callback_url'
            }), 400

        keyword = data['keyword']
        rewrite_id = data['rewrite_id']
        callback_url = data['callback_url']

        # Generate a unique task ID
        task_id = f"{rewrite_id}_{int(time.time())}"

        # Start processing in a new thread
        thread = threading.Thread(
            target=process_keyword_task, 
            args=(keyword, rewrite_id, callback_url)
        )
        thread.daemon = True
        thread.start()

        # Track the task
        active_tasks[task_id] = {
            'keyword': keyword,
            'rewrite_id': rewrite_id,
            'start_time': datetime.now().isoformat(),
            'status': 'running'
        }

        return jsonify({
            'status': 'accepted',
            'message': 'Processing started',
            'task_id': task_id,
            'keyword': keyword,
            'rewrite_id': rewrite_id
        })

    except Exception as e:
        logger.error(f"Error in process_keyword endpoint: {str(e)}")
        return jsonify({
            'status': 'error', 
            'message': str(e)
        }), 500



@app.route('/api/health', methods=['GET', 'POST'])
def health_check():
    """Health check endpoint for API monitoring"""
    return jsonify({
        'status': 'ok',
        'service': 'keyword_rewrite_api',
        'timestamp': datetime.now().isoformat(),
        'active_tasks': len(active_tasks),
        'gemini_model': GEMINI_MODEL,
        'gemini_api_key_configured': bool(GEMINI_API_KEY)
    })

@app.route('/health', methods=['GET', 'POST'])
def health_check_all():
    """Simple health check endpoint including configuration"""
    # Tải lại cấu hình để có thông tin mới nhất
    current_config = get_config()
    
    # Kiểm tra trạng thái các dịch vụ phụ thuộc
    search_status = "ok"
    scraper_status = "ok"
    rewriter_status = "ok"
    
    try:
        search_google_news("test", skip=999)  # Không thực hiện tìm kiếm thực tế
    except Exception as e:
        search_status = f"error: {str(e)}"
        
    try:
        # Kiểm tra kết nối Gemini API
        if not GEMINI_API_KEY:
            rewriter_status = "error: Missing Gemini API key"
    except Exception as e:
        rewriter_status = f"error: {str(e)}"
    
    return jsonify({
        'status': 'ok',
        'service': 'keyword_rewrite_api',
        'timestamp': datetime.now().isoformat(),
        'active_tasks': len(active_tasks),
        'dependencies': {
            'search': search_status,
            'scraper': scraper_status,
            'rewriter': rewriter_status
        },
        'config': {
            'gemini_model': current_config.get('GEMINI_MODEL'),
            'gemini_api_key_configured': bool(current_config.get('GEMINI_API_KEY')),
            'backend_url': current_config.get('BACKEND_URL'),
            'debug': current_config.get('DEBUG')
        }
    })

@app.route('/reload-config', methods=['POST'])
def reload_configuration():
    """Endpoint để tải lại cấu hình từ file .env"""
    global GEMINI_MODEL, GEMINI_API_KEY
    
    config = reload_config()
                
    # Cập nhật các biến toàn cục
    GEMINI_MODEL = config.get("GEMINI_MODEL", "gemini-1.5-flash-latest")
    GEMINI_API_KEY = config.get("GEMINI_API_KEY", "")
            
    # Cập nhật cấu hình Gemini API
    genai.configure(api_key=GEMINI_API_KEY)
    
    return jsonify({
        'status': 'ok',
        'message': 'Cấu hình đã được tải lại',
        'timestamp': datetime.now().isoformat(),
    })

if __name__ == "__main__":
    # Tải cấu hình
    config = get_config()
    
    # Lấy cấu hình từ config
    port = config.get("PORT_KEYWORD_REWRITE", 5003)
    host = config.get("HOST", "0.0.0.0")
    debug = config.get("DEBUG", False)
    
    logger.info(f"Starting Keyword Rewrite API server on {host}:{port}, debug={debug}")
    app.run(host=host, port=port, debug=debug) 
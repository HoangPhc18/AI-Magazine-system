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
        # Step 1: Search Google News for the keyword
        logger.info(f"Searching Google News for: {keyword}")
        article_url = search_google_news(keyword)
        
        if not article_url:
            logger.error(f"No article found for keyword: {keyword}")
            send_callback(callback_url, rewrite_id, "failed", 
                         error_message="Không tìm thấy bài viết nào cho từ khóa đã cho. Hệ thống đã lọc ra các trang không thể trích xuất nội dung như laodong.vn và baomoi.com.")
            return
            
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
                    article_url = search_google_news(keyword, skip=attempt)
                    if not article_url:
                        logger.error(f"No more articles found for keyword: {keyword}")
                        send_callback(callback_url, rewrite_id, "failed", 
                                    error_message=f"Không tìm thấy bài viết thay thế sau {attempt} lần thử. Hệ thống đã lọc ra các trang không thể trích xuất nội dung.")
                        return
                    continue
                else:
                    logger.error(f"Failed to extract content after {max_retries} attempts")
                    send_callback(callback_url, rewrite_id, "failed", 
                                error_message=f"Không thể trích xuất nội dung từ URL bài viết sau {max_retries} lần thử. Vui lòng thử từ khóa khác.")
                    return
            
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
                article_url = search_google_news(keyword, skip=attempt)
                if not article_url:
                    logger.error(f"No more articles found for keyword: {keyword}")
                    send_callback(callback_url, rewrite_id, "failed", 
                                 error_message=f"Không tìm thấy bài viết thay thế sau {attempt} lần thử. Hệ thống đã lọc ra các trang không thể trích xuất nội dung như laodong.vn và baomoi.com.")
                    return
        
        # Kiểm tra lại sau khi thử tối đa số lần
        if (not article_data or 
            not article_data.get("title") or 
            not article_data.get("content") or 
            len(article_data.get("content", "")) < 200):
            
            logger.error(f"Failed to extract content from URL after {max_retries} attempts")
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
            
            send_callback(callback_url, rewrite_id, "failed", 
                         source_url=article_url,
                         error_message=f"{error_detail} từ URL bài viết sau {max_retries} lần thử. Vui lòng thử từ khóa khác hoặc cung cấp URL bài viết cụ thể.")
            return
            
        # Step 3: Rewrite the content using Gemini
        logger.info(f"Rewriting content using Gemini (model: {GEMINI_MODEL})")
        rewritten_content = rewrite_content(article_data["title"], article_data["content"])
        
        if rewritten_content.startswith("Error:"):
            logger.error(f"Error rewriting content: {rewritten_content}")
            send_callback(callback_url, rewrite_id, "failed", 
                         source_url=article_url,
                         source_title=article_data["title"],
                         source_content=article_data["content"],
                         error_message=rewritten_content)
            return
            
        logger.info(f"Successfully rewrote content. Rewritten length: {len(rewritten_content)}")
        
        # Step 4: Send back the result
        send_callback(callback_url, rewrite_id, "completed", 
                     source_url=article_url,
                     source_title=article_data["title"],
                     rewritten_content=rewritten_content)
        
        logger.info(f"Keyword rewrite task completed for: {keyword} (ID: {rewrite_id})")
        
    except Exception as e:
        logger.error(f"Error processing keyword task: {str(e)}")
        send_callback(callback_url, rewrite_id, "failed", error_message=f"Processing error: {str(e)}")

def send_callback(callback_url, rewrite_id, status, source_url=None, source_title=None, 
               rewritten_content=None, error_message=None):
    """
    Gửi kết quả xử lý về cho backend thông qua callback URL
    
    Args:
        callback_url (str): URL to send results back to
        rewrite_id (int): ID của yêu cầu rewrite
        status (str): Trạng thái mới (completed, failed)
        source_url (str, optional): URL nguồn
        source_title (str, optional): Tiêu đề nguồn
        rewritten_content (str, optional): Nội dung đã viết lại
        error_message (str, optional): Thông báo lỗi nếu có
    """
    try:
        data = {
            'rewrite_id': rewrite_id,
            'status': status,
        }
        
        if source_url:
            data['source_url'] = source_url
            
        if source_title:
            data['source_title'] = source_title
            
        if rewritten_content:
            data['rewritten_content'] = rewritten_content
            
        if error_message:
            data['error_message'] = error_message
            
        # Use BACKEND_URL from environment and append path
        backend_url = config.get('BACKEND_URL')
        if backend_url:
            # Extract the path from the original callback_url
            parsed_url = urllib.parse.urlparse(callback_url)
            api_path = parsed_url.path
            
            # Replace any domain/host part with our backend URL
            fixed_callback_url = f"{backend_url}{api_path}"
            logger.info(f"Using BACKEND_URL from environment: {fixed_callback_url}")
            
            # Kiểm tra nếu đang chạy trong Docker container và giao tiếp với host
            if os.path.exists('/.dockerenv') and 'host.docker.internal' in backend_url:
                # Add custom headers for Host header to ensure proper virtual host routing
                headers = {
                    'Host': 'magazine.test',
                    'Content-Type': 'application/json'
                }
                
                # Attempt the request with custom headers
                logger.info(f"Sending callback with Host: magazine.test to: {fixed_callback_url}")
                response = requests.post(fixed_callback_url, json=data, headers=headers, timeout=30)
            else:
                # Nếu không chạy trong Docker, không cần header đặc biệt
                response = requests.post(fixed_callback_url, json=data, timeout=30)
        else:
            # Fallback to the original URL
            fixed_callback_url = callback_url
            response = requests.post(fixed_callback_url, json=data, timeout=30)
        
        if response.status_code >= 200 and response.status_code < 300:
            logger.info(f"Callback successfully sent to {fixed_callback_url}, status code: {response.status_code}")
        else:
            logger.error(f"Error sending callback: {response.status_code} - {response.text}")
    except Exception as e:
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
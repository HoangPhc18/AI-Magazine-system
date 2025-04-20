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

# Vô hiệu hóa tự động tải .env của Flask
os.environ["FLASK_SKIP_DOTENV"] = "1"

from flask import Flask, request, jsonify
from flask_cors import CORS
# from dotenv import load_dotenv

# Import our modules
from search import search_google_news
from scraper import extract_article_content
from rewriter import rewrite_content

# Set environment variables directly
# load_dotenv()
os.environ["PORT"] = "5003"
os.environ["HOST"] = "0.0.0.0"
os.environ["DEBUG"] = "False"
os.environ["OLLAMA_MODEL"] = "gemma2:latest"
os.environ["OLLAMA_HOST"] = "http://host.docker.internal:11434"

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

# Get Ollama model from environment or use default
OLLAMA_MODEL = os.getenv("OLLAMA_MODEL") or os.getenv("MODEL_NAME", "llama3:latest")
OLLAMA_HOST = os.getenv("OLLAMA_HOST", "http://localhost:11434")

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
            article_data = extract_article_content(article_url, attempt)
            
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
            
        # Step 3: Rewrite the content using Ollama
        logger.info(f"Rewriting content using Ollama (model: {OLLAMA_MODEL})")
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
            
        # Fix cho Docker: thay thế localhost/127.0.0.1 bằng host.docker.internal
        fixed_callback_url = callback_url
        if "localhost" in callback_url or "127.0.0.1" in callback_url:
            fixed_callback_url = callback_url.replace("localhost", "host.docker.internal").replace("127.0.0.1", "host.docker.internal")
            logger.info(f"Sending callback to fixed URL: {fixed_callback_url} (original was: {callback_url})")
        
        # Đảm bảo URL bao gồm cả port 8000 nếu không có port nào được chỉ định
        if "host.docker.internal" in fixed_callback_url and ":8000" not in fixed_callback_url:
            parsed_url = urllib.parse.urlparse(fixed_callback_url)
            if parsed_url.port is None:
                fixed_callback_url = fixed_callback_url.replace("host.docker.internal", "host.docker.internal:8000")
                logger.info(f"Added port 8000 to URL: {fixed_callback_url}")

        response = requests.post(fixed_callback_url, json=data, timeout=30)
        
        if response.status_code >= 200 and response.status_code < 300:
            logger.info(f"Callback successfully sent to {fixed_callback_url}")
        else:
            logger.error(f"Error sending callback: {response.status_code} - {response.text}")
    except Exception as e:
        logger.error(f"Error sending callback: {str(e)}")

@app.route('/api/keyword_rewrite/process', methods=['POST'])
def process_keyword():
    """
    Process a keyword rewrite request.
    """
    data = request.json
    
    if not data:
        return jsonify({"error": "No data provided"}), 400
        
    keyword = data.get('keyword')
    rewrite_id = data.get('rewrite_id')
    callback_url = data.get('callback_url')
    
    if not keyword:
        return jsonify({"error": "No keyword provided"}), 400
        
    if not rewrite_id:
        return jsonify({"error": "No rewrite_id provided"}), 400
        
    if not callback_url:
        return jsonify({"error": "No callback_url provided"}), 400
        
    # Start processing in a background thread
    thread = threading.Thread(
        target=process_keyword_task, 
        args=(keyword, rewrite_id, callback_url)
    )
    thread.daemon = True
    thread.start()
    
    return jsonify({
        "status": "processing",
        "message": f"Processing keyword: {keyword}"
    })

@app.route('/api/health', methods=['GET', 'POST'])
def health_check():
    """Endpoint kiểm tra trạng thái hoạt động của service"""
    return jsonify({
        'status': 'ok',
        'timestamp': datetime.now().isoformat(),
        'service': 'Keyword Rewrite API'
    })

@app.route('/health', methods=['GET', 'POST'])
def health_check_all():
    """
    Endpoint kiểm tra trạng thái hoạt động của dịch vụ.
    Cũng có thể được gọi bởi cron để lập lịch các tác vụ chung.
    """
    # Ghi log khi được gọi bởi cron
    if request.method == 'POST':
        logger.info("Health check triggered by scheduler (cron)")
        # Có thể thêm xử lý lịch trình tại đây nếu cần
    
    return jsonify({
        "status": "ok",
        "timestamp": datetime.now().isoformat(),
        "service": "AI Keyword Rewrite",
        "version": "1.0.0",
        "active_tasks": len(active_tasks)
    })

def extract_article_content(url, attempt_num=1):
    """
    Hàm trích xuất nội dung tối giản, không sử dụng signal.
    Thiết kế để hoạt động trong môi trường đa luồng.
    
    Args:
        url (str): URL của bài viết
        attempt_num (int): Số lần thử (để ghi log)
        
    Returns:
        dict: Dictionary chứa thông tin bài viết hoặc None nếu thất bại
    """
    logger.info(f"Extracting content from URL (attempt {attempt_num}/3): {url}")
    
    try:
        import requests
        from bs4 import BeautifulSoup
        from urllib.parse import urlparse
        
        # Headers chuẩn để tránh bị chặn
        headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
            "Accept-Language": "vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
        }
        
        # Lấy domain từ URL
        domain = urlparse(url).netloc
        logger.info(f"Extracting from domain: {domain}")
        
        # Dictionary các selector theo domain
        domain_selectors = {
            "vnexpress.net": {
                "title": "h1.title-detail",
                "content": "article.fck_detail",
            },
            "tuoitre.vn": {
                "title": "h1.article-title",
                "content": "div.article-content",
            },
            "thanhnien.vn": {
                "title": "h1.details__headline",
                "content": "div.details__content",
            },
            "dantri.com.vn": {
                "title": "h1.title-page",
                "content": "div.dt-news__content",
            },
            "vietnamnet.vn": {
                "title": "h1.content-detail-title",
                "content": "div.content-detail-body",
            },
            "24h.com.vn": {
                "title": "h1.brmCne, h1.clrTit",
                "content": "div.text-conent, div.brmDtl",
            },
            "danviet.vn": {
                "title": "h1.title",
                "content": "div.detail-content",
            },
            "vov.vn": {
                "title": "h1.article-title",
                "content": "div.article-body",
            },
            "nongnghiep.vn": {
                "title": "h1.title",
                "content": "div.detail-content-body",
            },
            "qdnd.vn": {
                "title": "h1.article-title",
                "content": "div.article-body",
            },
            "congthuong.vn": {
                "title": "h1.detail-title",
                "content": "div.detail-content-body",
            }
        }
        
        # Tải nội dung trang
        try:
            response = requests.get(url, headers=headers, timeout=10)
            response.raise_for_status()
            html_content = response.text
            logger.info(f"Successfully fetched content from {url}, content length: {len(html_content)}")
            
            # Lưu trang HTML để debug
            debug_filename = f"debug_{attempt_num}.html"
            with open(debug_filename, "w", encoding="utf-8") as f:
                f.write(html_content)
            
            # Phân tích HTML bằng BeautifulSoup
            soup = BeautifulSoup(html_content, 'html.parser')
            
            # Thử lấy thông tin từ selector dựa trên domain
            title = ""
            content = ""
            
            # Tìm trong domain_selectors
            found_selectors = False
            for domain_key, selectors in domain_selectors.items():
                if domain_key in domain:
                    found_selectors = True
                    # Lấy tiêu đề
                    title_elements = soup.select(selectors["title"])
                    if title_elements:
                        title = title_elements[0].get_text().strip()
                        logger.info(f"Found title with length {len(title)}")
                    
                    # Lấy nội dung
                    content_elements = soup.select(selectors["content"])
                    if content_elements:
                        # Lấy toàn bộ text từ các thẻ p
                        paragraphs = []
                        for p in content_elements[0].find_all('p'):
                            p_text = p.get_text().strip()
                            if p_text:
                                paragraphs.append(p_text)
                        
                        content = "\n\n".join(paragraphs)
                        logger.info(f"Found content with length {len(content)}")
                    break
            
            # Nếu không tìm thấy selector phù hợp, thử dùng trafilatura
            if not found_selectors or not title or not content:
                logger.info(f"Falling back to trafilatura for {url}")
                try:
                    import trafilatura
                    downloaded = trafilatura.fetch_url(url)
                    if downloaded:
                        result = trafilatura.extract(downloaded, output_format='json', include_links=True, 
                                                include_images=True, include_tables=True)
                        
                        import json
                        if result:
                            data = json.loads(result)
                            if not title:
                                title = data.get('title', '')
                            if not content or len(content) < 100:
                                content = data.get('text', '')
                            logger.info(f"Trafilatura extraction: title length={len(title)}, content length={len(content)}")
                except Exception as e:
                    logger.error(f"Trafilatura extraction failed: {str(e)}")
            
            # Nếu vẫn không tìm thấy, thử tìm tiêu đề từ thẻ title và nội dung từ bất kỳ thẻ p nào
            if not title or not content:
                logger.info("Using fallback extraction method")
                # Lấy tiêu đề từ thẻ title
                if soup.title and not title:
                    title = soup.title.string.strip()
                
                # Tìm tất cả các thẻ p có độ dài hợp lý
                if not content or len(content) < 100:
                    paragraphs = []
                    for p in soup.find_all('p'):
                        p_text = p.get_text().strip()
                        if len(p_text) > 30:  # Loại bỏ các thẻ p quá ngắn
                            paragraphs.append(p_text)
                    
                    if paragraphs:
                        content = "\n\n".join(paragraphs)
                        logger.info(f"Fallback extraction: found {len(paragraphs)} paragraphs, content length={len(content)}")
            
            # Kiểm tra và trả về kết quả
            if title and content and len(content) > 200:
                logger.info(f"Successfully extracted content from {url}")
                return {
                    'url': url,
                    'title': title,
                    'content': content,
                    'html_content': html_content[:10000]  # Giới hạn kích thước HTML
                }
            else:
                logger.warning(f"Extraction failed: title={bool(title)}, content_length={len(content) if content else 0}")
                return None
                
        except requests.RequestException as e:
            logger.error(f"Request error: {str(e)}")
            return None
            
    except Exception as e:
        logger.error(f"Extraction error: {str(e)}")
        return None

if __name__ == "__main__":
    # Các thiết lập từ biến môi trường
    port = int(os.getenv("PORT", 5003))
    host = os.getenv("HOST", "0.0.0.0")
    debug = os.getenv("DEBUG", "False").lower() == "true"
    
    logger.info(f"Starting Keyword Rewrite API on {host}:{port} (debug: {debug})")
    
    # Chạy ứng dụng Flask
    app.run(host=host, port=port, debug=debug) 
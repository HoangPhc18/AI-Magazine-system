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
os.environ["PORT"] = "5000"
os.environ["HOST"] = "0.0.0.0"
os.environ["DEBUG"] = "False"
os.environ["OLLAMA_MODEL"] = "gemma2:latest"
os.environ["OLLAMA_HOST"] = "http://localhost:11434"

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
            article_data = extract_article_content(article_url)
            
            if article_data["title"] and article_data["content"] and len(article_data["content"]) > 200:
                logger.info(f"Successfully extracted content - Title: {article_data['title']}, Content length: {len(article_data['content'])}")
                break
                
            # Log chi tiết hơn về vấn đề trích xuất
            if not article_data["title"]:
                logger.warning(f"Failed to extract title from {article_url}")
            elif not article_data["content"]:
                logger.warning(f"Failed to extract content from {article_url}")
            elif len(article_data["content"]) <= 200:
                logger.warning(f"Extracted content too short ({len(article_data['content'])} chars) from {article_url}")
            
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
        if not article_data["title"] or not article_data["content"] or len(article_data["content"]) < 200:
            logger.error(f"Failed to extract content from URL after {max_retries} attempts")
            error_detail = "Không thể trích xuất "
            if not article_data["title"]:
                error_detail += "tiêu đề"
            if not article_data["content"]:
                error_detail += " và nội dung" if not article_data["title"] else "nội dung"
            elif len(article_data["content"]) < 200:
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
                     source_content=article_data["content"],
                     rewritten_content=rewritten_content)
        
        logger.info(f"Keyword rewrite task completed for: {keyword} (ID: {rewrite_id})")
        
    except Exception as e:
        logger.error(f"Error processing keyword task: {str(e)}")
        send_callback(callback_url, rewrite_id, "failed", error_message=f"Processing error: {str(e)}")

def send_callback(callback_url, rewrite_id, status, source_url=None, source_title=None, 
                 source_content=None, rewritten_content=None, error_message=None):
    """
    Send results back to the callback URL.
    
    Args:
        callback_url (str): URL to send results back to
        rewrite_id (int): The ID of the rewrite record
        status (str): Status of the process (completed or failed)
        source_url (str, optional): URL of the source article
        source_title (str, optional): Title of the source article
        source_content (str, optional): Content of the source article
        rewritten_content (str, optional): Rewritten article content
        error_message (str, optional): Error message if any
    """
    try:
        data = {
            "rewrite_id": rewrite_id,
            "status": status,
            "source_url": source_url,
            "source_title": source_title,
            "source_content": source_content,
            "rewritten_content": rewritten_content,
            "error_message": error_message
        }
        
        # Sửa cứng callback URL để sử dụng đúng cổng PHP mặc định (8000)
        fixed_callback_url = "http://localhost:8000/api/keyword-rewrites/callback"
        logger.info(f"Sending callback to fixed URL: {fixed_callback_url} (original was: {callback_url})")
        
        response = requests.post(fixed_callback_url, json=data, timeout=30)
        
        if response.status_code == 200:
            logger.info(f"Callback sent successfully for ID: {rewrite_id}")
        else:
            logger.error(f"Error sending callback: {response.status_code} - {response.text}")
            
    except Exception as e:
        logger.error(f"Exception sending callback: {str(e)}")

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

@app.route('/api/health', methods=['GET'])
def health_check():
    """
    Simple health check endpoint.
    """
    return jsonify({
        "status": "ok",
        "service": "keyword_rewrite",
        "timestamp": datetime.now().isoformat(),
        "ollama_model": OLLAMA_MODEL,
        "ollama_host": OLLAMA_HOST
    })

@app.route('/health', methods=['GET'])
def health_check_all():
    """
    Endpoint kiểm tra trạng thái hoạt động của dịch vụ.
    """
    return jsonify({
        "status": "ok",
        "timestamp": datetime.now().isoformat(),
        "service": "AI Keyword Rewrite",
        "version": "1.0.0",
        "active_tasks": len(active_tasks)
    })

if __name__ == '__main__':
    port = int(os.getenv("PORT", 5000))
    host = os.getenv("HOST", "0.0.0.0")
    debug = os.getenv("DEBUG", "False").lower() in ('true', '1', 't')
    
    logger.info(f"Starting Keyword Rewrite API on {host}:{port} (Debug: {debug})")
    logger.info(f"Using Ollama model: {OLLAMA_MODEL}")
    logger.info(f"Using Ollama host: {OLLAMA_HOST}")
    
    app.run(host=host, port=port, debug=debug) 
#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
API server cho Rewrite - cho phép scheduler kích hoạt quá trình viết lại bài
Cung cấp REST API để chạy rewrite từ xa
"""

import os
import sys
import time
import json
import logging
import threading
import subprocess
from datetime import datetime
from flask import Flask, request, jsonify

# Import module config
from config import get_config, reload_config

# Thiết lập logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(f"rewrite_api_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger()

app = Flask(__name__)

# Biến toàn cục để theo dõi trạng thái đang chạy
running_process = None

@app.route('/health', methods=['GET'])
def health_check():
    """Endpoint kiểm tra trạng thái hoạt động của dịch vụ"""
    global running_process
    
    # Tải lại cấu hình để có thông tin mới nhất
    config = get_config()
    
    return jsonify({
        'status': 'ok',
        'timestamp': datetime.now().isoformat(),
        'service': 'Rewrite API',
        'running': running_process is not None,
        'last_run': getattr(running_process, 'start_time', None),
        'config': {
            'backend_url': config['BACKEND_URL'],
            'db_host': config['DB_HOST'],
            'default_provider': config['DEFAULT_PROVIDER']
        }
    })

@app.route('/reload-config', methods=['POST'])
def reload_configuration():
    """Endpoint để tải lại cấu hình từ file .env"""
    config = reload_config()
    return jsonify({
        'status': 'ok',
        'message': 'Cấu hình đã được tải lại',
        'timestamp': datetime.now().isoformat(),
    })

@app.route('/run', methods=['POST'])
def run_rewriter():
    """Endpoint chạy rewriter theo lịch trình"""
    global running_process
    
    # Kiểm tra nếu đang có quá trình đang chạy
    if running_process and running_process.is_running:
        return jsonify({
            'status': 'error',
            'message': 'Rewriter đang chạy',
            'start_time': running_process.start_time
        }), 409

    # Tạo quá trình chạy rewriter trong thread riêng biệt
    thread = threading.Thread(target=run_rewrite_process)
    thread.daemon = True
    thread.start()
    
    return jsonify({
        'status': 'started',
        'message': 'Rewriter đã được kích hoạt',
        'timestamp': datetime.now().isoformat()
    })

def run_rewrite_process():
    """Chạy quá trình rewrite"""
    global running_process
    
    # Đánh dấu quá trình đang chạy
    running_process = RewriteProcess()
    
    try:
        logger.info("Bắt đầu chạy quy trình viết lại bài...")
        # Chạy rewrite_from_db.py với các tùy chọn
        process = subprocess.run(
            [sys.executable, "rewrite_from_db.py", "--auto", "--limit", "10"],
            capture_output=True, 
            text=True
        )
        
        # Ghi log kết quả
        if process.returncode == 0:
            logger.info("Rewriter hoàn thành: %s", process.stdout)
            running_process.success = True
        else:
            logger.error("Rewriter lỗi (%d): %s", process.returncode, process.stderr)
            running_process.success = False
            running_process.error = process.stderr
            
    except Exception as e:
        logger.error(f"Lỗi khi chạy rewriter: {str(e)}")
        running_process.success = False
        running_process.error = str(e)
    
    # Đánh dấu thời gian kết thúc
    running_process.end_time = datetime.now().isoformat()
    running_process.is_running = False

class RewriteProcess:
    """Lớp đối tượng để theo dõi quá trình rewrite"""
    def __init__(self):
        self.start_time = datetime.now().isoformat()
        self.end_time = None
        self.is_running = True
        self.success = None
        self.error = None

def setup_logging():
    """Thiết lập cấu hình logging cho ứng dụng"""
    logging.basicConfig(
        level=logging.INFO,
        format="%(asctime)s - %(levelname)s - %(message)s",
        handlers=[
            logging.FileHandler(f"rewrite_api_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
            logging.StreamHandler(sys.stdout)
        ]
    )

@app.route('/rewrite-by-id/<int:article_id>', methods=['GET'])
def rewrite_by_id(article_id):
    """Rewrite a specific article by ID"""
    try:
        # Kiểm tra article_id
        if not article_id or not isinstance(article_id, int):
            return jsonify({
                'status': 'error',
                'message': 'Invalid article ID. Must be a valid integer.'
            }), 400
            
        # Gọi script rewrite_from_db.py với article_id
        result = subprocess.run(
            [sys.executable, 'rewrite_from_db.py', '--article-id', str(article_id)],
            capture_output=True,
            text=True
        )
        
        # Kiểm tra kết quả
        if result.returncode == 0:
            return jsonify({
                'status': 'success',
                'message': f'Article ID {article_id} has been processed using Gemini',
                'output': result.stdout
            })
        else:
            return jsonify({
                'status': 'error',
                'message': f'Failed to rewrite article ID {article_id}',
                'error': result.stderr
            }), 500
    except Exception as e:
        return jsonify({
            'status': 'error',
            'message': f'Error processing article ID {article_id}',
            'error': str(e)
        }), 500

@app.route('/unprocessed-articles', methods=['GET'])
def get_unprocessed_articles():
    """Get list of unprocessed articles"""
    try:
        # Kết nối database trực tiếp để lấy danh sách bài viết chưa xử lý
        from rewrite_from_db import connect_to_database
        
        connection = connect_to_database()
        if not connection:
            return jsonify({
                'status': 'error',
                'message': 'Failed to connect to database'
            }), 500
            
        try:
            cursor = connection.cursor(dictionary=True)
            
            # Truy vấn lấy các bài viết chưa được viết lại
            query = """
            SELECT id, title, source_name, DATE_FORMAT(created_at, '%Y-%m-%d') as date
            FROM articles
            WHERE content IS NOT NULL 
            AND LENGTH(content) > 100
            AND is_ai_rewritten = 0
            ORDER BY id ASC
            LIMIT 50
            """
            
            cursor.execute(query)
            articles = cursor.fetchall()
            
            # Đóng kết nối
            cursor.close()
            connection.close()
            
            return jsonify({
                'status': 'success',
                'count': len(articles),
                'articles': articles
            })
            
        except Exception as e:
            return jsonify({
                'status': 'error',
                'message': f'Database error: {str(e)}'
            }), 500
    except Exception as e:
        return jsonify({
            'status': 'error',
            'message': f'Unexpected error: {str(e)}'
        }), 500

@app.route('/rewrite-batch/<int:count>', methods=['GET'])
def rewrite_batch(count):
    """Rewrite a batch of articles in order of ID"""
    try:
        # Kiểm tra count
        if not count or count <= 0:
            return jsonify({
                'status': 'error',
                'message': 'Invalid count. Must be a positive integer.'
            }), 400
            
        # Giới hạn số lượng bài viết tối đa được xử lý một lần
        if count > 10:
            count = 10
            
        # Gọi script rewrite_from_db.py với số lượng bài viết cần xử lý
        result = subprocess.run(
            [sys.executable, 'rewrite_from_db.py', '--limit', str(count)],
            capture_output=True,
            text=True
        )
        
        # Kiểm tra kết quả
        if result.returncode == 0:
            return jsonify({
                'status': 'success',
                'message': f'Processed {count} articles using Gemini',
                'output': result.stdout
            })
        else:
            return jsonify({
                'status': 'error',
                'message': f'Failed to process articles',
                'error': result.stderr
            }), 500
    except Exception as e:
        return jsonify({
            'status': 'error',
            'message': f'Error processing batch rewrite',
            'error': str(e)
        }), 500

if __name__ == "__main__":
    # Thiết lập logging
    setup_logging()
    
    # Tải cấu hình
    config = get_config()
    
    # Chạy ứng dụng Flask
    port = config["PORT_REWRITE"]
    host = config["HOST"]
    debug = config["DEBUG"]
    
    logger.info(f"Starting Rewrite API on {host}:{port} (debug: {debug})")
    app.run(host=host, port=port, debug=debug) 
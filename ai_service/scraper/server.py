#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
API server cho Scraper - cho phép scheduler kích hoạt quá trình scraping
Cung cấp REST API để chạy scraper từ xa
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

# Thiết lập logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(f"scraper_api_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
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
    
    return jsonify({
        'status': 'ok',
        'timestamp': datetime.now().isoformat(),
        'service': 'Scraper API',
        'running': running_process is not None,
        'last_run': getattr(running_process, 'start_time', None),
    })

@app.route('/run', methods=['POST'])
def run_scraper():
    """Endpoint chạy scraper theo lịch trình"""
    global running_process
    
    # Kiểm tra nếu đang có quá trình đang chạy
    if running_process and running_process.is_running:
        return jsonify({
            'status': 'error',
            'message': 'Scraper đang chạy',
            'start_time': running_process.start_time
        }), 409

    # Tạo quá trình chạy scraper trong thread riêng biệt
    thread = threading.Thread(target=run_scraper_process)
    thread.daemon = True
    thread.start()
    
    return jsonify({
        'status': 'started',
        'message': 'Scraper đã được kích hoạt',
        'timestamp': datetime.now().isoformat()
    })

def run_scraper_process():
    """Chạy quá trình scraper"""
    global running_process
    
    # Đánh dấu quá trình đang chạy
    running_process = ScraperProcess()
    
    try:
        logger.info("Bắt đầu chạy quy trình scraping...")
        # Chạy main.py với các tùy chọn
        process = subprocess.run(
            [sys.executable, "main.py", "--auto-send"],
            capture_output=True, 
            text=True
        )
        
        # Ghi log kết quả
        if process.returncode == 0:
            logger.info("Scraper hoàn thành: %s", process.stdout)
            running_process.success = True
        else:
            logger.error("Scraper lỗi (%d): %s", process.returncode, process.stderr)
            running_process.success = False
            running_process.error = process.stderr
            
    except Exception as e:
        logger.error(f"Lỗi khi chạy scraper: {str(e)}")
        running_process.success = False
        running_process.error = str(e)
    
    # Đánh dấu thời gian kết thúc
    running_process.end_time = datetime.now().isoformat()
    running_process.is_running = False

class ScraperProcess:
    """Lớp đối tượng để theo dõi quá trình scraping"""
    def __init__(self):
        self.start_time = datetime.now().isoformat()
        self.end_time = None
        self.is_running = True
        self.success = None
        self.error = None

if __name__ == '__main__':
    port = int(os.environ.get('PORT', 8080))
    app.run(host='0.0.0.0', port=port, debug=False) 
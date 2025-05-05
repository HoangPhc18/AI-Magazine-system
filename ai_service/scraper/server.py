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

# Import module config
from config import get_config, reload_config

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

# Biến global để theo dõi trạng thái scraper
is_scraper_running = False
scraper_start_time = None
scraper_process = None

# Biến toàn cục để theo dõi trạng thái đang chạy
running_process = None

@app.route('/health', methods=['GET'])
def health_check():
    """
    Endpoint kiểm tra sức khỏe API
    
    Returns:
        JSON: Trạng thái API
    """
    # Kiểm tra kết nối database và API backend
    config = get_config()
    backend_url = config.get('BACKEND_URL')
    db_host = config.get('DB_HOST')
    
    global is_scraper_running
    global scraper_start_time
    
    # Trả về thông tin health check
    return jsonify({
        'status': 'ok',
        'service': 'Scraper API',
        'timestamp': datetime.now().isoformat(),
        'running': is_scraper_running,
        'last_run': scraper_start_time.isoformat() if scraper_start_time else None,
        'config': {
            'backend_url': backend_url,
            'db_host': db_host
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
def run_scraper():
    """
    Kích hoạt quá trình scraping
    
    Returns:
        JSON: Kết quả kích hoạt scraper
    """
    global is_scraper_running
    global scraper_start_time
    
    # Kiểm tra xem scraper đã đang chạy chưa
    if is_scraper_running:
        logger.warning("Yêu cầu bị từ chối: Scraper đang chạy")
        return jsonify({
            'status': 'error',
            'message': 'Scraper đang chạy',
            'start_time': scraper_start_time.isoformat() if scraper_start_time else None
        }), 409
    
    # Lấy tham số từ request (nếu có)
    data = request.get_json(silent=True) or {}
    category_id = data.get('category_id')
    subcategory_id = data.get('subcategory_id')
    
    # Chạy scraper trong một process riêng biệt
    success, message = run_scraper_process(category_id, subcategory_id)
    
    if success:
        return jsonify({
            'status': 'started',
            'message': 'Scraper đã được kích hoạt',
            'timestamp': datetime.now().isoformat(),
            'category_id': category_id,
            'subcategory_id': subcategory_id
        })
    else:
        return jsonify({
            'status': 'error',
            'message': message,
            'timestamp': datetime.now().isoformat()
        }), 500

@app.route('/scrape-subcategory', methods=['POST'])
def scrape_subcategory():
    """
    Kích hoạt quá trình scraping cho một danh mục con cụ thể
    
    Returns:
        JSON: Kết quả kích hoạt scraper
    """
    global is_scraper_running
    global scraper_start_time
    
    # Kiểm tra xem scraper đã đang chạy chưa
    if is_scraper_running:
        logger.warning("Yêu cầu bị từ chối: Scraper đang chạy")
        return jsonify({
            'status': 'error',
            'message': 'Scraper đang chạy',
            'start_time': scraper_start_time.isoformat() if scraper_start_time else None
        }), 409
    
    # Lấy tham số từ request
    data = request.get_json(silent=True) or {}
    category_id = data.get('category_id')
    subcategory_id = data.get('subcategory_id')
    
    # Kiểm tra tham số bắt buộc
    if not category_id:
        return jsonify({
            'status': 'error',
            'message': 'Thiếu tham số category_id',
            'timestamp': datetime.now().isoformat()
        }), 400
        
    if not subcategory_id:
        return jsonify({
            'status': 'error',
            'message': 'Thiếu tham số subcategory_id',
            'timestamp': datetime.now().isoformat()
        }), 400
    
    # Chạy scraper trong một process riêng biệt
    success, message = run_scraper_process(category_id, subcategory_id)
    
    if success:
        return jsonify({
            'status': 'started',
            'message': 'Scraper cho danh mục con đã được kích hoạt',
            'timestamp': datetime.now().isoformat(),
            'category_id': category_id,
            'subcategory_id': subcategory_id
        })
    else:
        return jsonify({
            'status': 'error',
            'message': message,
            'timestamp': datetime.now().isoformat()
        }), 500

def run_scraper_process(category_id=None, subcategory_id=None):
    """
    Hàm chạy quá trình scraping trong một process riêng biệt
    
    Args:
        category_id (int, optional): ID danh mục cần scrape
        subcategory_id (int, optional): ID danh mục con cần scrape
    """
    lock_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'scraper.lock')
    
    # Kiểm tra xem scraper đã đang chạy không (thông qua lock file)
    if os.path.exists(lock_path):
        try:
            # Đọc PID từ lock file
            with open(lock_path, 'r') as f:
                pid = int(f.read().strip())
            
            # Kiểm tra PID có tồn tại không
            if is_process_running(pid):
                start_time = os.path.getmtime(lock_path)
                start_time_str = datetime.fromtimestamp(start_time).strftime('%Y-%m-%d %H:%M:%S')
                logger.warning(f"Scraper đang chạy với PID {pid} (bắt đầu lúc {start_time_str})")
                return False, f"Scraper đang chạy với PID {pid} (bắt đầu lúc {start_time_str})"
            else:
                # PID không còn tồn tại, xóa lock file
                os.remove(lock_path)
        except Exception as e:
            # Có lỗi khi đọc lock file, xóa nó
            logger.error(f"Lỗi khi kiểm tra lock file: {str(e)}")
            os.remove(lock_path)
    
    # Tạo lock file mới với PID hiện tại
    with open(lock_path, 'w') as f:
        f.write(str(os.getpid()))

    # Cài đặt thời gian bắt đầu
    global scraper_start_time
    scraper_start_time = datetime.now()
    
    try:
        logger.info("Bắt đầu chạy quy trình scraping...")
        
        # Đường dẫn đến script main.py
        script_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'main.py')
        
        # Đảm bảo script tồn tại
        if not os.path.exists(script_path):
            logger.error(f"Không tìm thấy script: {script_path}")
            return False, f"Không tìm thấy script: {script_path}"
        
        # Xây dựng tham số dòng lệnh
        cmd = [sys.executable, script_path]
        
        if category_id and subcategory_id:
            # Chỉ scrape một danh mục con cụ thể
            cmd.extend(["--category", str(category_id), "--subcategory", str(subcategory_id), "--auto-send", "--use-subcategories"])
        elif category_id:
            # Chỉ scrape một danh mục cụ thể
            cmd.extend(["--category", str(category_id), "--auto-send", "--use-subcategories"])
        else:
            # Scrape tất cả danh mục
            cmd.extend(["--all", "--auto-send", "--use-subcategories"])
        
        # Chạy script trong process con với các tham số
        process = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE
        )
        
        # Lưu PID của process con vào lock file
        with open(lock_path, 'w') as f:
            f.write(str(process.pid))
        
        # Thiết lập biến global để theo dõi process
        global scraper_process
        scraper_process = process
        
        # Khởi động thread để theo dõi quá trình
        monitor_thread = threading.Thread(target=monitor_scraper_process, args=(process, lock_path))
        monitor_thread.daemon = True
        monitor_thread.start()
        
        # Đánh dấu scraper đang chạy
        global is_scraper_running
        is_scraper_running = True
        
        return True, "Scraper đã được kích hoạt"
        
    except Exception as e:
        logger.error(f"Lỗi khi chạy scraper: {str(e)}")
        
        # Xóa lock file nếu có lỗi
        if os.path.exists(lock_path):
            os.remove(lock_path)
            
        return False, f"Lỗi khi chạy scraper: {str(e)}"

class ScraperProcess:
    """Lớp đối tượng để theo dõi quá trình scraping"""
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
            logging.FileHandler(f"scraper_api_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
            logging.StreamHandler(sys.stdout)
        ]
    )

def is_process_running(pid):
    """
    Kiểm tra xem một process có đang chạy không dựa trên PID
    
    Args:
        pid (int): Process ID cần kiểm tra
        
    Returns:
        bool: True nếu process đang chạy, False nếu không
    """
    try:
        # Với POSIX, kiểm tra process có tồn tại không
        if os.name == 'posix':
            return os.path.exists(f'/proc/{pid}')
        # Với Windows, sử dụng tasklist để kiểm tra
        elif os.name == 'nt':
            import subprocess
            output = subprocess.check_output(['tasklist', '/FI', f'PID eq {pid}']).decode()
            return str(pid) in output
        else:
            # Cho các hệ điều hành khác, giả định process đang chạy
            return True
    except Exception as e:
        logger.error(f"Lỗi khi kiểm tra process {pid}: {str(e)}")
        return False

def monitor_scraper_process(process, lock_path):
    """
    Theo dõi process scraper trong một thread riêng biệt
    
    Args:
        process (subprocess.Popen): Process scraper đang chạy
        lock_path (str): Đường dẫn đến lock file
    """
    global is_scraper_running
    global scraper_process
    
    # Ghi log PID của process
    logger.info(f"Đang theo dõi scraper process với PID {process.pid}")
    
    try:
        # Đọc output từ process
        stdout, stderr = process.communicate()
        
        # Ghi log output
        if stdout:
            logger.info(f"Scraper output: {stdout.decode('utf-8')}")
        if stderr:
            logger.error(f"Scraper error: {stderr.decode('utf-8')}")
        
        # Kiểm tra kết quả trả về
        if process.returncode == 0:
            logger.info("Scraper hoàn thành thành công")
        else:
            logger.error(f"Scraper kết thúc với mã lỗi: {process.returncode}")
    except Exception as e:
        logger.error(f"Lỗi khi theo dõi scraper process: {str(e)}")
    finally:
        # Đánh dấu scraper đã kết thúc
        is_scraper_running = False
        scraper_process = None
        
        # Xóa lock file nếu tồn tại
        if os.path.exists(lock_path):
            os.remove(lock_path)
            logger.info(f"Đã xóa lock file: {lock_path}")
        
        logger.info("Scraper đã kết thúc")

if __name__ == "__main__":
    # Thiết lập logging
    setup_logging()
    
    # Tải cấu hình
    config = get_config()
    
    # Đảm bảo các thư mục output tồn tại
    output_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "output")
    if not os.path.exists(output_dir):
        os.makedirs(output_dir)
        logger.info(f"Created output directory: {output_dir}")
    
    # Chạy ứng dụng Flask
    port = config["PORT_SCRAPER"]
    host = config["HOST"]
    debug = config["DEBUG"]
    
    logger.info(f"Starting Scraper API on {host}:{port} (debug: {debug})")
    app.run(host=host, port=port, debug=debug) 
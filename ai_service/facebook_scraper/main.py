#!/usr/bin/env python
import os
import sys
import time
import json
import logging
import argparse
import threading
import subprocess
from flask import Flask, request, jsonify
from scraper_facebook import setup_driver, get_facebook_posts
from datetime import datetime
import traceback

# Import module config
from config import get_config, reload_config

# Tải cấu hình
config = get_config()

# Tạo thư mục logs nếu chưa tồn tại
os.makedirs("logs", exist_ok=True)

# Cấu hình logging với UTF-8 encoding để hỗ trợ tiếng Việt
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("logs/facebook_scraper.log", encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger("facebook_scraper")

# Fix lỗi encoding khi ghi log với tiếng Việt
if sys.stdout.encoding != 'utf-8':
    sys.stdout.reconfigure(encoding='utf-8', errors='backslashreplace')

# Khởi tạo Flask app
app = Flask(__name__)

# Danh sách các job đang chạy
jobs = {
    'active': {},    # Các job đang chạy
    'completed': {}  # Các job đã hoàn thành
}

def ensure_chromedriver():
    """Đảm bảo ChromeDriver được cài đặt với phiên bản phù hợp"""
    try:
        logger.info("Verifying ChromeDriver installation...")
        
        # Chạy script install_chromedriver.py
        result = subprocess.run(
            [sys.executable, os.path.join(os.path.dirname(os.path.abspath(__file__)), "install_chromedriver.py")],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )
        
        if result.returncode != 0:
            logger.error(f"ChromeDriver installation failed: {result.stderr}")
            return False
        else:
            logger.info("ChromeDriver installed successfully")
            return True
    except Exception as e:
        logger.error(f"Error installing ChromeDriver: {str(e)}")
        return False

def scrape_task(job_id, url, use_profile, chrome_profile, limit, headless):
    """Hàm chạy task scraping trong một thread riêng"""
    try:
        logger.info(f"Job {job_id} - Bat dau scraping tu URL: {url}")
        jobs['active'][job_id]['status'] = 'running'
        
        # Đảm bảo ChromeDriver đã được cài đặt đúng phiên bản
        ensure_chromedriver()
        
        # Thực hiện scraping
        try:
            # Sử dụng hàm mới setup_driver từ module scraper_facebook
            posts = get_facebook_posts(
                url=url, 
                use_profile=use_profile, 
                chrome_profile=chrome_profile,
                limit=limit,
                save_to_db=True,
                headless=headless
            )
            
            # Cập nhật thông tin job
            completion_info = {
                'status': 'completed',
                'count': len(posts),
                'completed_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }
            
            logger.info(f"Job {job_id} hoan thanh, thu thap duoc {len(posts)} bai viet")
            
        except Exception as e:
            error_message = str(e)
            logger.error(f"Job {job_id} - Loi: {error_message}")
            logger.error(traceback.format_exc())
            
            completion_info = {
                'status': 'failed',
                'error': error_message,
                'completed_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }
        
        # Chuyển job từ active sang completed
        job_info = jobs['active'].pop(job_id, {})
        job_info.update(completion_info)
        jobs['completed'][job_id] = job_info
        
    except Exception as e:
        logger.error(f"Loi trong thread scraping {job_id}: {str(e)}")
        logger.error(traceback.format_exc())
        
        # Đảm bảo job luôn được đánh dấu là đã hoàn thành
        if job_id in jobs['active']:
            job_info = jobs['active'].pop(job_id, {})
            job_info.update({
                'status': 'failed',
                'error': f"Thread error: {str(e)}",
                'completed_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            })
            jobs['completed'][job_id] = job_info

@app.route('/api/scrape', methods=['POST'])
def scrape():
    """API endpoint để bắt đầu scraping"""
    try:
        data = request.json
        
        # Kiểm tra tham số đầu vào
        url = data.get('url')
        use_profile = data.get('use_profile', config.get('USE_CHROME_PROFILE', True))
        chrome_profile = data.get('chrome_profile', 'Default')
        limit = int(data.get('limit', config.get('DEFAULT_POST_LIMIT', 10)))
        headless = data.get('headless', config.get('HEADLESS', True))
        
        if not url:
            return jsonify({'error': 'URL is required'}), 400
        
        # Tạo job ID
        job_id = f"job_{len(jobs['active']) + len(jobs['completed']) + 1}"
        
        # Lưu thông tin job
        jobs['active'][job_id] = {
            'id': job_id,
            'url': url,
            'use_profile': use_profile,
            'chrome_profile': chrome_profile,
            'limit': limit,
            'headless': headless,
            'status': 'pending',
            'started_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
        
        # Bắt đầu thread scraping
        thread = threading.Thread(
            target=scrape_task,
            args=(job_id, url, use_profile, chrome_profile, limit, headless)
        )
        thread.daemon = True
        thread.start()
        
        return jsonify({'message': 'Scraping job started', 'job_id': job_id})
    
    except Exception as e:
        logger.error(f"Loi khi bat dau scraping: {str(e)}")
        logger.error(traceback.format_exc())
        return jsonify({'error': str(e)}), 500

@app.route('/api/jobs/<job_id>', methods=['GET'])
def get_job_status(job_id):
    """API endpoint để kiểm tra trạng thái job"""
    try:
        # Kiểm tra xem job có trong danh sách jobs đang chạy không
        if job_id in jobs['active']:
            return jsonify(jobs['active'][job_id])
        
        # Kiểm tra xem job có trong danh sách jobs đã hoàn thành không
        if job_id in jobs['completed']:
            return jsonify(jobs['completed'][job_id])
        
        # Không tìm thấy job
        return jsonify({'error': 'Job not found'}), 404
    
    except Exception as e:
        logger.error(f"Loi khi kiem tra trang thai job: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/api/jobs', methods=['GET'])
def get_all_jobs():
    """API endpoint để lấy danh sách tất cả các jobs"""
    try:
        return jsonify(jobs)
    except Exception as e:
        logger.error(f"Loi khi lay danh sach jobs: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/health', methods=['GET'])
def health_check():
    """API endpoint để kiểm tra trạng thái của service"""
    try:
        # Lấy lại cấu hình mới nhất
        current_config = get_config()
        
        return jsonify({
            'status': 'ok',
            'service': 'facebook_scraper_api',
            'active_jobs': len(jobs['active']),
            'completed_jobs': len(jobs['completed']),
            'config': {
                'facebook_username_configured': bool(current_config.get('FACEBOOK_USERNAME')),
                'use_chrome_profile': current_config.get('USE_CHROME_PROFILE'),
                'chrome_profile_path': current_config.get('CHROME_PROFILE_PATH'),
                'headless': current_config.get('HEADLESS')
            }
        })
    except Exception as e:
        logger.error(f"Loi khi kiem tra health: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/reload-config', methods=['POST'])
def reload_configuration():
    """Endpoint để tải lại cấu hình từ file .env"""
    try:
        new_config = reload_config()
        logger.info("Đã tải lại cấu hình thành công")
        
        return jsonify({
            'status': 'ok',
            'message': 'Cấu hình đã được tải lại thành công',
            'config': {
                'facebook_username_configured': bool(new_config.get('FACEBOOK_USERNAME')),
                'use_chrome_profile': new_config.get('USE_CHROME_PROFILE'),
                'chrome_profile_path': new_config.get('CHROME_PROFILE_PATH'),
                'headless': new_config.get('HEADLESS')
            }
        })
    except Exception as e:
        logger.error(f"Lỗi khi tải lại cấu hình: {str(e)}")
        return jsonify({'error': str(e)}), 500

def run_server(host='0.0.0.0', port=None):
    """Chạy Flask server"""
    # Sử dụng config thay vì trực tiếp từ biến môi trường
    if port is None:
        port = config.get('PORT_FACEBOOK_SCRAPER', 5004)
    
    host = config.get('HOST', '0.0.0.0')
    debug = config.get('DEBUG', False)
    
    logger.info(f"Bat dau Facebook Scraper API service tren {host}:{port}, debug={debug}")
    app.run(host=host, port=port, debug=debug)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Facebook Scraper Service')
    parser.add_argument('--host', type=str, default=None, help='Host to bind to')
    parser.add_argument('--port', type=int, default=None, help='Port to bind to')
    args = parser.parse_args()

    # Đảm bảo ChromeDriver được cài đặt trước khi khởi động dịch vụ
    if not ensure_chromedriver():
        logger.error("Failed to ensure ChromeDriver is properly installed. Continuing anyway...")
    
    # Ưu tiên tham số dòng lệnh nếu có
    host = args.host if args.host is not None else config.get('HOST', '0.0.0.0')
    port = args.port if args.port is not None else config.get('PORT_FACEBOOK_SCRAPER', 5004)
        
    run_server(host=host, port=port) 
#!/usr/bin/env python
import os
import sys
import time
import json
import logging
import argparse
import threading
from flask import Flask, request, jsonify
from scraper_facebook import setup_driver, get_facebook_posts
from datetime import datetime
import traceback

# Cấu hình logging với UTF-8 encoding để hỗ trợ tiếng Việt
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("facebook_scraper.log", encoding='utf-8'),
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

def scrape_task(job_id, url, use_profile, chrome_profile, limit, headless):
    """Hàm chạy task scraping trong một thread riêng"""
    try:
        logger.info(f"Job {job_id} - Bat dau scraping tu URL: {url}")
        jobs['active'][job_id]['status'] = 'running'
        
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
        use_profile = data.get('use_profile', True)
        chrome_profile = data.get('chrome_profile', 'Default')
        limit = int(data.get('limit', 10))
        headless = data.get('headless', True)
        
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
        return jsonify({
            'status': 'ok',
            'active_jobs': len(jobs['active']),
            'completed_jobs': len(jobs['completed'])
        })
    except Exception as e:
        logger.error(f"Loi khi kiem tra health: {str(e)}")
        return jsonify({'error': str(e)}), 500

def run_server(host='0.0.0.0', port=None):
    """Chạy Flask server"""
    # Sử dụng biến môi trường PORT nếu có, mặc định là 5004
    if port is None:
        port = int(os.getenv('PORT', 5004))
    logger.info(f"Bat dau Facebook Scraper API service tren {host}:{port}")
    app.run(host=host, port=port, debug=False)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Facebook Scraper Service')
    parser.add_argument('--host', type=str, default='0.0.0.0', help='Host to bind to')
    parser.add_argument('--port', type=int, default=None, help='Port to bind to (defaults to PORT env or 5004)')
    args = parser.parse_args()
    
    run_server(host=args.host, port=args.port) 
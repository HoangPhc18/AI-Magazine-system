#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Script để kiểm tra và tạo thư mục output nếu chưa tồn tại
"""

import os
import sys
import logging
from datetime import datetime

# Cấu hình logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger()

def setup_directories():
    """
    Kiểm tra và tạo các thư mục cần thiết
    """
    # Thư mục gốc của scraper
    scraper_dir = os.path.dirname(os.path.abspath(__file__))
    logger.info(f"Thư mục scraper: {scraper_dir}")
    
    # Thư mục output trong scraper
    output_dir = os.path.join(scraper_dir, "output")
    if not os.path.exists(output_dir):
        logger.info(f"Tạo thư mục output: {output_dir}")
        os.makedirs(output_dir, exist_ok=True)
    else:
        logger.info(f"Thư mục output đã tồn tại: {output_dir}")
    
    # Thư mục output trong Docker container (nếu đang chạy trong Docker)
    docker_output = "/app/output"
    if os.path.exists("/app"):
        if not os.path.exists(docker_output):
            logger.info(f"Tạo thư mục output trong Docker: {docker_output}")
            os.makedirs(docker_output, exist_ok=True)
        else:
            logger.info(f"Thư mục output trong Docker đã tồn tại: {docker_output}")
    
    # Kiểm tra quyền ghi
    try:
        test_file = os.path.join(output_dir, ".test_write")
        with open(test_file, "w") as f:
            f.write("test")
        os.remove(test_file)
        logger.info(f"Kiểm tra quyền ghi tại {output_dir}: Thành công")
    except Exception as e:
        logger.error(f"Không thể ghi vào thư mục {output_dir}: {str(e)}")
    
    # Kiểm tra quyền ghi trong Docker (nếu đang chạy trong Docker)
    if os.path.exists("/app"):
        try:
            test_file = os.path.join(docker_output, ".test_write")
            with open(test_file, "w") as f:
                f.write("test")
            os.remove(test_file)
            logger.info(f"Kiểm tra quyền ghi tại {docker_output}: Thành công")
        except Exception as e:
            logger.error(f"Không thể ghi vào thư mục {docker_output}: {str(e)}")
    
    logger.info("Setup thư mục hoàn tất")

if __name__ == "__main__":
    setup_directories() 
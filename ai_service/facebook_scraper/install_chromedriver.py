#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import sys
import subprocess
import logging
import requests
import zipfile
import io
import shutil
import time
import traceback

# Thiết lập logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s", 
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger("chromedriver_installer")

def main():
    logger.info("Starting ChromeDriver installation")
    
    try:
        # Phát hiện phiên bản Chrome hiện tại
        chrome_version_cmd = "google-chrome --version" if os.path.exists("/usr/bin/google-chrome") else "chrome --version"
        try:
            chrome_version_output = subprocess.check_output(chrome_version_cmd, shell=True, text=True)
            chrome_version = chrome_version_output.strip().split()[2]
            major_version = chrome_version.split('.')[0]  # Lấy phiên bản chính
            logger.info(f"Detected Chrome version: {chrome_version} (Major: {major_version})")
            
            # Lấy thông tin Chrome for Testing từ API của Google
            logger.info("Fetching Chrome for Testing metadata...")
            testing_url = "https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json"
            testing_response = requests.get(testing_url)
            
            if testing_response.status_code == 200:
                versions_data = testing_response.json()
                stable_version = versions_data.get("channels", {}).get("Stable", {}).get("version", "")
                logger.info(f"Latest Chrome for Testing stable version: {stable_version}")
                
                # Tìm URL tải ChromeDriver
                chromedriver_url = None
                stable_downloads = versions_data.get("channels", {}).get("Stable", {}).get("downloads", {}).get("chromedriver", [])
                for download in stable_downloads:
                    if download.get("platform") == "linux64":
                        chromedriver_url = download.get("url")
                        break
                
                # Nếu không tìm thấy trong Stable, thử với Canary
                if not chromedriver_url:
                    logger.warning("ChromeDriver not found in Stable channel, trying Canary...")
                    canary_downloads = versions_data.get("channels", {}).get("Canary", {}).get("downloads", {}).get("chromedriver", [])
                    for download in canary_downloads:
                        if download.get("platform") == "linux64":
                            chromedriver_url = download.get("url")
                            break
                
                # Nếu tìm thấy URL, tải ChromeDriver
                if chromedriver_url:
                    logger.info(f"Downloading ChromeDriver from: {chromedriver_url}")
                    chromedriver_response = requests.get(chromedriver_url)
                    
                    if chromedriver_response.status_code == 200:
                        # Đường dẫn tới chromedriver
                        chromedriver_path = "/usr/local/bin/chromedriver"
                        backup_path = f"{chromedriver_path}.bak"
                        
                        # Sao lưu chromedriver hiện tại nếu tồn tại
                        if os.path.exists(chromedriver_path):
                            shutil.copy2(chromedriver_path, backup_path)
                            logger.info(f"Backed up existing ChromeDriver to {backup_path}")
                        
                        # Lưu và giải nén ChromeDriver
                        temp_dir = "/tmp/chromedriver_temp"
                        os.makedirs(temp_dir, exist_ok=True)
                        
                        with zipfile.ZipFile(io.BytesIO(chromedriver_response.content)) as zip_file:
                            # Giải nén tất cả các file vào thư mục tạm
                            zip_file.extractall(temp_dir)
                            
                            # Tìm file chromedriver trong các thư mục con
                            chromedriver_files = []
                            for root, dirs, files in os.walk(temp_dir):
                                for file in files:
                                    if file == "chromedriver" or file == "chromedriver.exe":
                                        chromedriver_files.append(os.path.join(root, file))
                            
                            if chromedriver_files:
                                # Lấy file đầu tiên tìm thấy
                                source_file = chromedriver_files[0]
                                shutil.copy2(source_file, chromedriver_path)
                                os.chmod(chromedriver_path, 0o755)
                                logger.info(f"Successfully installed ChromeDriver to {chromedriver_path}")
                                
                                # Xóa thư mục tạm
                                shutil.rmtree(temp_dir, ignore_errors=True)
                                
                                # Kiểm tra phiên bản
                                try:
                                    version_output = subprocess.check_output([chromedriver_path, "--version"], text=True)
                                    logger.info(f"Installed ChromeDriver version: {version_output.strip()}")
                                    return True
                                except Exception as e:
                                    logger.warning(f"Could not verify ChromeDriver version: {str(e)}")
                                    # Vẫn return True vì đã cài đặt thành công
                                    return True
                            else:
                                logger.error("ChromeDriver executable not found in the downloaded package")
                        
                        # Xóa thư mục tạm nếu còn tồn tại
                        shutil.rmtree(temp_dir, ignore_errors=True)
                    else:
                        logger.error(f"Failed to download ChromeDriver. Status code: {chromedriver_response.status_code}")
                else:
                    logger.error("ChromeDriver download URL not found in Chrome for Testing metadata")
            else:
                logger.error(f"Failed to fetch Chrome for Testing metadata. Status code: {testing_response.status_code}")
                
            # Thử phương pháp thay thế nếu trên đã thất bại
            if not chromedriver_url or not (chromedriver_response.status_code == 200 and chromedriver_files):
                logger.warning("Falling back to alternative ChromeDriver installation method...")
                fallback_version = "114.0.5735.90"  # Phiên bản có thể tương thích với nhiều phiên bản Chrome
                fallback_url = f"https://chromedriver.storage.googleapis.com/{fallback_version}/chromedriver_linux64.zip"
                
                logger.info(f"Downloading fallback ChromeDriver from: {fallback_url}")
                fallback_response = requests.get(fallback_url)
                
                if fallback_response.status_code == 200:
                    chromedriver_path = "/usr/local/bin/chromedriver"
                    
                    with zipfile.ZipFile(io.BytesIO(fallback_response.content)) as zip_file:
                        with zip_file.open("chromedriver") as source, open(chromedriver_path, "wb") as target:
                            shutil.copyfileobj(source, target)
                        
                        # Đặt quyền thực thi
                        os.chmod(chromedriver_path, 0o755)
                        logger.info(f"Successfully installed fallback ChromeDriver {fallback_version}")
                        return True
                else:
                    logger.error(f"Fallback ChromeDriver download failed. Status code: {fallback_response.status_code}")
                    return False
            
        except Exception as e:
            logger.error(f"Error detecting Chrome version: {str(e)}")
            logger.error(traceback.format_exc())
            return False
            
    except Exception as e:
        logger.error(f"Error installing ChromeDriver: {str(e)}")
        logger.error(traceback.format_exc())
        return False

if __name__ == "__main__":
    if main():
        logger.info("ChromeDriver installation completed successfully")
        sys.exit(0)
    else:
        logger.error("ChromeDriver installation failed")
        sys.exit(1) 
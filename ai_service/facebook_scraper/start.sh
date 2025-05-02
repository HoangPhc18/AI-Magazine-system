#!/bin/bash

echo "Setting up environment for Facebook Scraper..."

# Đảm bảo đường dẫn thư mục tồn tại
mkdir -p logs cookies chrome_profile
chmod -R 777 logs cookies chrome_profile

# Cài đặt ChromeDriver phù hợp
echo "Installing ChromeDriver..."
python install_chromedriver.py

# Kiểm tra phiên bản Chrome và ChromeDriver
echo "Chrome version: $(google-chrome --version)"
echo "ChromeDriver version: $(chromedriver --version)"

# Khởi động dịch vụ
echo "Starting Facebook Scraper API service..."
python main.py 
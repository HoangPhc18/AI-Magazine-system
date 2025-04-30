FROM ubuntu:22.04

# Thiết lập biến môi trường để tránh các thông báo trong quá trình cài đặt
ENV DEBIAN_FRONTEND=noninteractive
ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1
ENV TZ=Asia/Ho_Chi_Minh
ENV PIP_DEFAULT_TIMEOUT=7200
ENV PIP_RETRIES=10

# Thiết lập múi giờ
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Cập nhật danh sách các gói và cài đặt các gói cần thiết
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-dev \
    wget \
    unzip \
    gnupg \
    cron \
    curl \
    iputils-ping \
    net-tools \
    lsof \
    xvfb \
    libxi6 \
    libgconf-2-4 \
    fonts-liberation \
    libasound2 \
    libatk-bridge2.0-0 \
    libatk1.0-0 \
    libcups2 \
    libdbus-1-3 \
    libgdk-pixbuf2.0-0 \
    libgtk-3-0 \
    libnspr4 \
    libnss3 \
    libxcomposite1 \
    libxdamage1 \
    libxrandr2 \
    libxss1 \
    libxtst6 \
    nginx \
    chromium-browser \
    && rm -rf /var/lib/apt/lists/*

# Tạo liên kết để các ứng dụng sử dụng Chrome có thể sử dụng Chromium
RUN ln -sf /usr/bin/chromium-browser /usr/bin/google-chrome

# Tạo liên kết cho Python 3
RUN ln -s /usr/bin/python3 /usr/bin/python

# Nâng cấp pip
RUN pip3 install --no-cache-dir --upgrade pip --timeout 7200 --retries 10

# Thiết lập thư mục làm việc trong container
WORKDIR /app

# Tạo cấu trúc thư mục
RUN mkdir -p /app/scraper /app/rewrite /app/keyword_rewrite /app/facebook_scraper /app/facebook_rewrite /var/log/ai_service

# --- SCRAPER SERVICE ---
WORKDIR /app/scraper
COPY scraper/requirements.txt .
RUN pip3 install --no-cache-dir -r requirements.txt --timeout 7200 --retries 10
RUN pip3 install --no-cache-dir lxml_html_clean flask --timeout 7200 --retries 10
COPY scraper/ .
# Xóa thư mục venv nếu có
RUN rm -rf .venv venv
RUN mkdir -p output logs
RUN chmod +x server.py main.py
COPY scraper/crontab.txt /etc/cron.d/scraper-crontab
RUN chmod 0644 /etc/cron.d/scraper-crontab
RUN crontab /etc/cron.d/scraper-crontab

# --- REWRITE SERVICE ---
WORKDIR /app/rewrite
COPY rewrite/requirements.txt .
RUN pip3 install --no-cache-dir -r requirements.txt --timeout 7200 --retries 10
RUN pip3 install --no-cache-dir flask google-generativeai>=0.3.0 --timeout 7200 --retries 10
COPY rewrite/ .
# Xóa thư mục venv nếu có
RUN rm -rf .venv venv
RUN mkdir -p logs
RUN chmod +x server.py rewrite_from_db.py
COPY rewrite/crontab.txt /etc/cron.d/rewrite-crontab
RUN chmod 0644 /etc/cron.d/rewrite-crontab
RUN crontab /etc/cron.d/rewrite-crontab

# --- KEYWORD REWRITE SERVICE ---
WORKDIR /app/keyword_rewrite
COPY keyword_rewrite/requirements.txt .
RUN pip3 install --no-cache-dir -r requirements.txt --timeout 7200 --retries 10
RUN pip3 install --no-cache-dir lxml_html_clean --timeout 7200 --retries 10
COPY keyword_rewrite/ .
# Xóa thư mục venv nếu có
RUN rm -rf .venv venv
RUN chmod +x api.py

# --- FACEBOOK SCRAPER SERVICE ---
WORKDIR /app/facebook_scraper
COPY facebook_scraper/requirements.txt .
RUN pip3 install --no-cache-dir -r requirements.txt --timeout 7200 --retries 10
RUN pip3 install --no-cache-dir selenium webdriver-manager --timeout 7200 --retries 10
COPY facebook_scraper/ .
# Xóa thư mục venv nếu có
RUN rm -rf .venv venv
RUN mkdir -p logs cookies chrome_profile
RUN chmod +x main.py

# --- FACEBOOK REWRITE SERVICE ---
WORKDIR /app/facebook_rewrite
COPY facebook_rewrite/requirements.txt .
RUN pip3 install --no-cache-dir -r requirements.txt --timeout 7200 --retries 10
COPY facebook_rewrite/ .
# Xóa thư mục venv nếu có
RUN rm -rf .venv venv
RUN mkdir -p logs
RUN chmod +x app.py

# --- NGINX CONFIGURATION ---
WORKDIR /app
COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY .env /app/.env
COPY start.sh /app/start.sh
RUN chmod +x /app/start.sh

# Xóa thư mục venv chính nếu có
RUN rm -rf venv

# --- HEALTHCHECK ---
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:55025/health || exit 1

# --- EXPOSE PORT ---
EXPOSE 55025

# --- ENTRYPOINT ---
ENTRYPOINT ["/app/start.sh"] 
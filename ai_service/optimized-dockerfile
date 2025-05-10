FROM python:3.10-slim as base

# Thiết lập biến môi trường cơ bản
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    TZ=Asia/Ho_Chi_Minh \
    PIP_DEFAULT_TIMEOUT=7200 \
    PIP_RETRIES=10 \
    PIP_NO_CACHE_DIR=1

# Thiết lập múi giờ
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Cài đặt các gói hệ thống cần thiết cho runtime
RUN apt-get update && apt-get install -y --no-install-recommends \
    wget curl iputils-ping net-tools lsof cron nginx \
    && rm -rf /var/lib/apt/lists/*

# Tạo thư mục làm việc
WORKDIR /app
RUN mkdir -p /app/scraper /app/rewrite /app/keyword_rewrite /app/facebook_scraper /app/facebook_rewrite /var/log/ai_service

# --- STAGE 2: INSTALL CHROME ---
FROM base as chrome
RUN apt-get update && apt-get install -y --no-install-recommends \
    gnupg xvfb libxi6 libgconf-2-4 fonts-liberation libasound2 \
    libatk-bridge2.0-0 libatk1.0-0 libcups2 libdbus-1-3 \
    libgdk-pixbuf2.0-0 libgtk-3-0 libnspr4 libnss3 libxcomposite1 \
    libxdamage1 libxrandr2 libxss1 libxtst6 jq unzip \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt Google Chrome
RUN wget -q https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb \
    && apt-get update \
    && apt-get install -y --no-install-recommends ./google-chrome-stable_current_amd64.deb \
    && rm google-chrome-stable_current_amd64.deb \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt ChromeDriver từ Chrome for Testing API
RUN CHROME_VERSION=$(google-chrome --version | awk '{print $3}') \
    && CHROME_MAJOR_VERSION=$(echo $CHROME_VERSION | cut -d. -f1) \
    && curl -s https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json > /tmp/chrome_versions.json \
    && CHROMEDRIVER_URL=$(jq -r '.channels.Stable.downloads.chromedriver[] | select(.platform=="linux64") | .url' /tmp/chrome_versions.json) \
    && if [ -z "$CHROMEDRIVER_URL" ]; then \
         CHROMEDRIVER_URL=$(jq -r '.channels.Canary.downloads.chromedriver[] | select(.platform=="linux64") | .url' /tmp/chrome_versions.json); \
       fi \
    && if [ ! -z "$CHROMEDRIVER_URL" ]; then \
         mkdir -p /tmp/chromedriver \
         && wget -q -O /tmp/chromedriver.zip "$CHROMEDRIVER_URL" \
         && unzip -q /tmp/chromedriver.zip -d /tmp/chromedriver \
         && find /tmp/chromedriver -name chromedriver -type f -exec cp {} /usr/local/bin/chromedriver \; \
         && chmod +x /usr/local/bin/chromedriver; \
       else \
         apt-get update && apt-get install -y chromium-chromedriver \
         && ln -sf /usr/lib/chromium/chromedriver /usr/local/bin/chromedriver; \
       fi \
    && rm -rf /tmp/chrome_versions.json /tmp/chromedriver.zip /tmp/chromedriver \
    && rm -rf /var/lib/apt/lists/*

# --- STAGE 3: INSTALL PYTHON DEPENDENCIES ---
FROM chrome as dependencies

# Nâng cấp pip
RUN pip install --no-cache-dir --upgrade pip

# Gộp tất cả các requirements để loại bỏ các gói trùng lặp
COPY scraper/requirements.txt rewrite/requirements.txt keyword_rewrite/requirements.txt \
     facebook_scraper/requirements.txt facebook_rewrite/requirements.txt /tmp/

# Tạo file requirements hợp nhất
RUN cat /tmp/*.txt > /tmp/combined_requirements.txt

# Cài đặt các gói Python - CPU only cho torch để giảm kích thước
RUN pip install --no-cache-dir -r /tmp/combined_requirements.txt \
    && if grep -q torch /tmp/combined_requirements.txt; then \
         pip uninstall -y torch \
         && pip install --no-cache-dir torch==2.0.1 --index-url https://download.pytorch.org/whl/cpu; \
       fi

# Cài đặt thêm các package bị thiếu - web và API
RUN pip install --no-cache-dir flask_cors flask 

# Scraping và browser automation
RUN pip install --no-cache-dir selenium webdriver-manager bs4 beautifulsoup4 trafilatura lxml_html_clean lxml
RUN pip install --no-cache-dir "charset-normalizer>=3.1.0"

# Utilities
RUN pip install --no-cache-dir psutil gunicorn pillow python-dotenv requests unidecode

# NLP và AI
RUN pip install --no-cache-dir "google-generativeai>=0.3.0" langchain openai tiktoken spacy nltk

# Cơ sở dữ liệu
RUN pip install --no-cache-dir mysql-connector-python

# Các gói đảm bảo phiên bản tương thích
RUN pip install --no-cache-dir "urllib3<2.0.0" 
RUN pip install --no-cache-dir "pydantic>=1.10.0,<2.0.0"

# --- STAGE 4: FINAL IMAGE ---
FROM dependencies as final

# Copy các file service
COPY scraper/ /app/scraper/
COPY rewrite/ /app/rewrite/
COPY keyword_rewrite/ /app/keyword_rewrite/
COPY facebook_scraper/ /app/facebook_scraper/
COPY facebook_rewrite/ /app/facebook_rewrite/

# Xóa các thư mục môi trường ảo nếu có
RUN find /app -name "venv" -type d -exec rm -rf {} +
RUN find /app -name ".venv" -type d -exec rm -rf {} +

# Tạo thư mục logs và cấu hình quyền
RUN mkdir -p /app/scraper/logs /app/scraper/output \
    /app/rewrite/logs \
    /app/keyword_rewrite/logs \
    /app/facebook_scraper/logs /app/facebook_scraper/cookies /app/facebook_scraper/chrome_profile \
    /app/facebook_rewrite/logs

# Cấu hình quyền thực thi
RUN chmod +x /app/scraper/server.py /app/scraper/main.py \
    /app/rewrite/server.py /app/rewrite/rewrite_from_db.py \
    /app/keyword_rewrite/api.py \
    /app/facebook_scraper/main.py \
    /app/facebook_rewrite/app.py

# Cấu hình crontab
COPY scraper/crontab.txt /etc/cron.d/scraper-crontab
COPY rewrite/crontab.txt /etc/cron.d/rewrite-crontab
RUN chmod 0644 /etc/cron.d/scraper-crontab /etc/cron.d/rewrite-crontab \
    && crontab /etc/cron.d/scraper-crontab \
    && crontab /etc/cron.d/rewrite-crontab

# Copy cấu hình nginx và script khởi động
COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY .env /app/.env
COPY start.sh /app/start.sh
RUN chmod +x /app/start.sh

# Dọn dẹp cuối cùng để giảm kích thước image
RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && find /usr/local -name '*.pyc' -delete \
    && find /usr/local -name '__pycache__' -delete

# Cấu hình healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:55025/health || exit 1

# Expose port
EXPOSE 55025

# Entrypoint
ENTRYPOINT ["/bin/bash", "/app/start.sh"] 
FROM python:3.10-slim

WORKDIR /app

# Cài đặt các gói cần thiết
RUN apt-get update && apt-get install -y \
    wget \
    unzip \
    gnupg \
    cron \
    curl \
    iputils-ping \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt Google Chrome driver dependencies
RUN wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | apt-key add - \
    && echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google.list \
    && apt-get update && apt-get install -y google-chrome-stable \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements và cài đặt thư viện Python
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Cài thêm thư viện cần thiết
RUN pip install --no-cache-dir lxml_html_clean
RUN pip install --no-cache-dir flask

# Copy source code
COPY . .

# Tạo thư mục output nếu chưa tồn tại
RUN mkdir -p output

# Tạo thư mục logs để lưu log từ cron
RUN mkdir -p logs

# Thiết lập quyền thực thi
RUN chmod +x server.py main.py

# Thiết lập crontab
COPY crontab.txt /etc/cron.d/scraper-crontab
RUN chmod 0644 /etc/cron.d/scraper-crontab
RUN crontab /etc/cron.d/scraper-crontab

# Mở cổng cho API server
EXPOSE 5001

# Tạo script khởi động và thiết lập quyền
RUN echo '#!/bin/bash' > /entrypoint.sh
RUN echo 'service cron start' >> /entrypoint.sh
RUN echo 'cd /app && python server.py' >> /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Chạy script khởi động
CMD ["/entrypoint.sh"] 
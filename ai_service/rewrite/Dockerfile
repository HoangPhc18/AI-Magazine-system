FROM python:3.10-slim

WORKDIR /app

# Cài đặt các gói cần thiết
RUN apt-get update && apt-get install -y cron \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements và cài đặt thư viện Python
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Cài thêm Flask và Google Generative AI để chạy API server và Gemini API
RUN pip install --no-cache-dir flask google-generativeai>=0.3.0

# Copy source code
COPY . .

# Tạo thư mục logs để lưu log từ cron
RUN mkdir -p logs

# Thiết lập quyền thực thi
RUN chmod +x server.py rewrite_from_db.py

# Thiết lập crontab
COPY crontab.txt /etc/cron.d/rewrite-crontab
RUN chmod 0644 /etc/cron.d/rewrite-crontab
RUN crontab /etc/cron.d/rewrite-crontab

# Mở cổng cho API server
EXPOSE 5002

# Tạo script khởi động và thiết lập quyền
RUN echo '#!/bin/bash' > /entrypoint.sh
RUN echo 'service cron start' >> /entrypoint.sh
RUN echo 'cd /app && python server.py' >> /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Chạy script khởi động
CMD ["/entrypoint.sh"] 
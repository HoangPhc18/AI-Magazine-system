FROM python:3.10-slim

WORKDIR /app

# Cài đặt các phần mềm cần thiết cho Chrome và Selenium
RUN apt-get update && apt-get install -y \
    wget \
    gnupg \
    unzip \
    curl \
    xvfb \
    libxi6 \
    libgconf-2-4 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt Google Chrome
RUN wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | apt-key add - \
    && echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google-chrome.list \
    && apt-get update \
    && apt-get install -y google-chrome-stable \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements và cài đặt thư viện Python
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
RUN pip install --no-cache-dir lxml_html_clean

# Copy source code
COPY . .

# Thiết lập quyền thực thi
RUN chmod +x api.py

# Mở cổng cho API
EXPOSE 5003

# Thiết lập biến môi trường Python
ENV PYTHONPATH=/app

# Chạy API
CMD ["python", "api.py"] 
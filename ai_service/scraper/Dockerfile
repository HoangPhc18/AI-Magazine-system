FROM python:3.9-slim

WORKDIR /app

# Cài đặt các phần mềm cần thiết
RUN apt-get update && apt-get install -y \
    wget \
    unzip \
    gnupg \
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

# Cài thêm Flask để chạy API server
RUN pip install --no-cache-dir flask

# Copy source code
COPY . .

# Tạo thư mục output
RUN mkdir -p output

# Thiết lập quyền thực thi
RUN chmod +x server.py main.py

# Mở cổng cho API server
EXPOSE 8080

# Chạy API server thay vì main.py trực tiếp
CMD ["python", "server.py"] 
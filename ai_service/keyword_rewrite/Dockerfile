FROM python:3.9-slim

WORKDIR /app

# Copy requirements và cài đặt thư viện Python
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy source code
COPY . .

# Thiết lập quyền thực thi
RUN chmod +x api.py

# Mở cổng cho API
EXPOSE 5000

# Chạy API (luôn chạy)
CMD ["python", "api.py"] 
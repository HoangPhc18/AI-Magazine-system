FROM python:3.9-slim

WORKDIR /app

# Copy requirements và cài đặt thư viện Python
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Cài thêm Flask để chạy API server
RUN pip install --no-cache-dir flask

# Copy source code
COPY . .

# Thiết lập quyền thực thi
RUN chmod +x server.py rewrite_from_db.py

# Mở cổng cho API server
EXPOSE 8080

# Chạy API server thay vì rewrite_from_db.py trực tiếp
CMD ["python", "server.py"] 
#!/bin/bash

# Thiết lập logging
log_file="/var/log/ai_service/startup.log"
mkdir -p /var/log/ai_service

# Hàm ghi log
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$log_file"
}

# Cài đặt net-tools nếu chưa có
if ! command -v netstat &> /dev/null; then
    log "Đang cài đặt net-tools..."
    apt-get update && apt-get install -y net-tools
fi

# Xử lý các biến môi trường
log "Đang cấu hình biến môi trường..."

# Ưu tiên các biến môi trường được truyền vào qua Docker run
# Nếu không có, sử dụng các giá trị từ file .env
if [ -z "$BACKEND_URL" ]; then
    # Kiểm tra xem chúng ta có đang chạy trong Docker không
    if [ -f /.dockerenv ]; then
        log "Phát hiện đang chạy trong Docker. Sử dụng host.docker.internal..."
        export BACKEND_URL="http://host.docker.internal"
        export DB_HOST="host.docker.internal"
        export OLLAMA_HOST="http://host.docker.internal:11434"
        export OLLAMA_BASE_URL="http://host.docker.internal:11434"
    fi
fi

# Thông tin về biến môi trường
log "BACKEND_URL = $BACKEND_URL"
log "DB_HOST = $DB_HOST"
log "OLLAMA_HOST = $OLLAMA_HOST"

# Cập nhật file .env với các giá trị từ biến môi trường
if [ -n "$BACKEND_URL" ]; then
    log "Cập nhật BACKEND_URL thành $BACKEND_URL"
    sed -i "s|BACKEND_URL=.*|BACKEND_URL=$BACKEND_URL|g" /app/.env
fi

if [ -n "$DB_HOST" ]; then
    log "Cập nhật DB_HOST thành $DB_HOST"
    sed -i "s|DB_HOST=.*|DB_HOST=$DB_HOST|g" /app/.env
fi

if [ -n "$OLLAMA_HOST" ]; then
    log "Cập nhật OLLAMA_HOST thành $OLLAMA_HOST"
    sed -i "s|OLLAMA_HOST=.*|OLLAMA_HOST=$OLLAMA_HOST|g" /app/.env
    sed -i "s|OLLAMA_BASE_URL=.*|OLLAMA_BASE_URL=$OLLAMA_HOST|g" /app/.env
fi

# Khởi động các dịch vụ
log "Bắt đầu khởi động các dịch vụ AI..."

# Khởi động Nginx
log "Khởi động Nginx..."
service nginx start
log "Nginx đã được khởi động."

# Khởi động cron
log "Khởi động cron service..."
service cron start
log "Cron đã được khởi động."

# Khởi động Scraper Service
log "Khởi động Scraper Service (Port 5001)..."
cd /app/scraper
python server.py > /var/log/ai_service/scraper.log 2>&1 &
log "Scraper Service đã được khởi động."

# Khởi động Rewrite Service
log "Khởi động Rewrite Service (Port 5002)..."
cd /app/rewrite
python server.py > /var/log/ai_service/rewrite.log 2>&1 &
log "Rewrite Service đã được khởi động."

# Khởi động Keyword Rewrite Service
log "Khởi động Keyword Rewrite Service (Port 5003)..."
cd /app/keyword_rewrite
python api.py > /var/log/ai_service/keyword_rewrite.log 2>&1 &
log "Keyword Rewrite Service đã được khởi động."

# Khởi động Facebook Scraper Service
log "Khởi động Facebook Scraper Service (Port 5004)..."
cd /app/facebook_scraper
python main.py --all > /var/log/ai_service/facebook_scraper.log 2>&1 &
log "Facebook Scraper Service đã được khởi động."

# Khởi động Facebook Rewrite Service
log "Khởi động Facebook Rewrite Service (Port 5005)..."
cd /app/facebook_rewrite
python app.py > /var/log/ai_service/facebook_rewrite.log 2>&1 &
log "Facebook Rewrite Service đã được khởi động."

# Kiểm tra các dịch vụ đã khởi động
log "Đang kiểm tra các dịch vụ..."
sleep 5

# Hàm kiểm tra port
check_port() {
    local port=$1
    local service_name=$2
    
    # Kiểm tra bằng netstat nếu có
    if command -v netstat &> /dev/null; then
        if netstat -tuln | grep ":$port" > /dev/null; then
            log "$service_name: Hoạt động (Port $port)"
            return 0
        fi
    # Nếu không có netstat, sử dụng lsof hoặc ss
    elif command -v lsof &> /dev/null; then
        if lsof -i :$port > /dev/null 2>&1; then
            log "$service_name: Hoạt động (Port $port)"
            return 0
        fi
    elif command -v ss &> /dev/null; then
        if ss -tuln | grep ":$port" > /dev/null; then
            log "$service_name: Hoạt động (Port $port)"
            return 0
        fi
    # Cuối cùng thử dùng curl
    else
        if curl -s -o /dev/null -w "%{http_code}" http://localhost:$port > /dev/null 2>&1; then
            log "$service_name: Hoạt động (Port $port)"
            return 0
        fi
    fi
    
    log "$service_name: Không hoạt động (Port $port)"
    return 1
}

# Kiểm tra tất cả các dịch vụ
check_port 5001 "Scraper Service"
check_port 5002 "Rewrite Service"
check_port 5003 "Keyword Rewrite Service"
check_port 5004 "Facebook Scraper Service"
check_port 5005 "Facebook Rewrite Service"

# Giữ container chạy
log "Container đã khởi động thành công. Giữ container chạy..."
tail -f /var/log/ai_service/*.log 
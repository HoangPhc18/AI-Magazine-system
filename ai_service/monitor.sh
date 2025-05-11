#!/bin/bash

# Script giám sát hệ thống Magazine AI
# Kiểm tra kết nối giữa các thành phần và trạng thái Docker container

# Hàm hiển thị thông báo màu sắc
print_color() {
    COLOR=$1
    MESSAGE=$2
    
    case $COLOR in
        "green") echo -e "\e[32m$MESSAGE\e[0m" ;;
        "red") echo -e "\e[31m$MESSAGE\e[0m" ;;
        "yellow") echo -e "\e[33m$MESSAGE\e[0m" ;;
        "blue") echo -e "\e[34m$MESSAGE\e[0m" ;;
        *) echo "$MESSAGE" ;;
    esac
}

# Hàm kiểm tra API endpoint với timeout
check_endpoint() {
    URL=$1
    DESCRIPTION=$2
    TIMEOUT=5
    
    # Sử dụng curl để kiểm tra endpoint với timeout
    if curl --output /dev/null --silent --fail --max-time $TIMEOUT "$URL"; then
        print_color "green" "✓ $DESCRIPTION: Kết nối thành công"
        return 0
    else
        print_color "red" "✗ $DESCRIPTION: Không thể kết nối"
        return 1
    fi
}

# Hàm kiểm tra MySQL
check_mysql() {
    HOST=$1
    USER=$2
    PASS=$3
    
    if command -v mysql >/dev/null 2>&1; then
        if mysql -h "$HOST" -u "$USER" -p"$PASS" -e "SELECT 1" >/dev/null 2>&1; then
            print_color "green" "✓ MySQL: Kết nối thành công tới $HOST"
            return 0
        else
            print_color "red" "✗ MySQL: Không thể kết nối tới $HOST"
            return 1
        fi
    else
        print_color "yellow" "⚠ MySQL client không được cài đặt, bỏ qua kiểm tra"
        return 2
    fi
}

# Kiểm tra Docker container
check_docker_container() {
    CONTAINER_NAME=$1
    
    if docker ps --format '{{.Names}}' | grep -q "$CONTAINER_NAME"; then
        STATUS=$(docker inspect --format='{{.State.Status}}' "$CONTAINER_NAME")
        if [ "$STATUS" == "running" ]; then
            print_color "green" "✓ Container $CONTAINER_NAME: $STATUS"
            return 0
        else
            print_color "red" "✗ Container $CONTAINER_NAME: $STATUS"
            return 1
        fi
    else
        print_color "red" "✗ Container $CONTAINER_NAME: không tồn tại"
        return 1
    fi
}

# Tiêu đề
print_color "blue" "===== MAGAZINE AI SYSTEM - MONITORING ====="
echo ""

# Phát hiện hệ điều hành
print_color "yellow" "Đang phát hiện môi trường..."

if grep -q Microsoft /proc/version || grep -q WSL /proc/version; then
    print_color "green" "Phát hiện WSL/Windows environment"
    ENVIRONMENT="windows"
    HOST_IP="host.docker.internal"
elif [ "$(uname)" == "Darwin" ]; then
    print_color "green" "Phát hiện macOS environment"
    ENVIRONMENT="macos"
    HOST_IP="host.docker.internal"
else
    print_color "green" "Phát hiện Linux environment"
    ENVIRONMENT="linux"
    HOST_IP="172.17.0.1"
fi

print_color "green" "Host IP được cấu hình: $HOST_IP"
echo ""

# Kiểm tra kết nối đến Backend
print_color "blue" "Kiểm tra kết nối tới Backend API..."
check_endpoint "http://$HOST_IP/api/health" "Backend API"
BACKEND_STATUS=$?
echo ""

# Kiểm tra kết nối đến Ollama
print_color "blue" "Kiểm tra kết nối tới Ollama..."
check_endpoint "http://$HOST_IP:11434/api/version" "Ollama API"
OLLAMA_STATUS=$?
echo ""

# Kiểm tra MySQL
print_color "blue" "Kiểm tra kết nối tới MySQL..."
# Đọc các thông tin từ file .env nếu có
if [ -f ".env" ]; then
    DB_USER=$(grep -E "^DB_USERNAME=" .env | cut -d= -f2)
    DB_PASS=$(grep -E "^DB_PASSWORD=" .env | cut -d= -f2)
    if [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
        print_color "yellow" "⚠ Không thể đọc thông tin DB_USERNAME hoặc DB_PASSWORD từ file .env"
        DB_USER="root"
        DB_PASS=""
    fi
else
    print_color "yellow" "⚠ Không tìm thấy file .env, sử dụng thông tin mặc định"
    DB_USER="root"
    DB_PASS=""
fi

check_mysql "$HOST_IP" "$DB_USER" "$DB_PASS"
MYSQL_STATUS=$?
echo ""

# Kiểm tra Docker container
print_color "blue" "Kiểm tra Docker container..."
# Tìm container liên quan đến AI service
CONTAINERS=$(docker ps --format '{{.Names}}' | grep -E "ai|rewrite|scraper" | tr '\n' ' ')

if [ -z "$CONTAINERS" ]; then
    print_color "yellow" "⚠ Không tìm thấy container AI Service nào đang chạy"
else
    print_color "green" "Các container đang chạy: $CONTAINERS"
    
    # Kiểm tra trạng thái của từng container
    for container in $CONTAINERS; do
        check_docker_container "$container"
    done
fi
echo ""

# Kiểm tra log của các container
print_color "blue" "Kiểm tra log container gần nhất..."
if [ -z "$CONTAINERS" ]; then
    print_color "yellow" "⚠ Không có container để kiểm tra log"
else
    # Lấy container đầu tiên để kiểm tra
    FIRST_CONTAINER=$(echo $CONTAINERS | cut -d' ' -f1)
    print_color "yellow" "10 dòng log gần nhất của container $FIRST_CONTAINER:"
    docker logs --tail 10 "$FIRST_CONTAINER" 2>&1
fi
echo ""

# Tóm tắt trạng thái
print_color "blue" "===== TÓM TẮT TRẠNG THÁI SYSTEM ====="
echo "Backend API: $([ $BACKEND_STATUS -eq 0 ] && echo "OK" || echo "FAIL")"
echo "Ollama API: $([ $OLLAMA_STATUS -eq 0 ] && echo "OK" || echo "FAIL")"
echo "MySQL: $([ $MYSQL_STATUS -eq 0 ] && echo "OK" || [ $MYSQL_STATUS -eq 2 ] && echo "SKIP" || echo "FAIL")"

# Kiểm tra tổng thể
if [ $BACKEND_STATUS -eq 0 ] && [ $OLLAMA_STATUS -eq 0 ] && ([ $MYSQL_STATUS -eq 0 ] || [ $MYSQL_STATUS -eq 2 ]); then
    print_color "green" "✓ Hệ thống hoạt động bình thường!"
else
    print_color "red" "✗ Hệ thống có vấn đề, vui lòng kiểm tra các thành phần!"
fi
echo ""

# Hướng dẫn xử lý sự cố
print_color "yellow" "Hướng dẫn xử lý sự cố:"
if [ $BACKEND_STATUS -ne 0 ]; then
    print_color "yellow" "- Backend API: Kiểm tra Laravel backend có đang chạy không"
    print_color "yellow" "  Khởi động lại: cd backend && php artisan serve --host=$HOST_IP"
fi

if [ $OLLAMA_STATUS -ne 0 ]; then
    print_color "yellow" "- Ollama: Kiểm tra service Ollama có đang chạy không"
    if [ "$ENVIRONMENT" == "linux" ]; then
        print_color "yellow" "  Khởi động lại: systemctl restart ollama"
    else
        print_color "yellow" "  Khởi động lại Ollama application trên Windows/macOS"
    fi
fi

if [ $MYSQL_STATUS -eq 1 ]; then
    print_color "yellow" "- MySQL: Kiểm tra service MySQL có đang chạy không"
    if [ "$ENVIRONMENT" == "linux" ]; then
        print_color "yellow" "  Khởi động lại: systemctl restart mysql"
    else
        print_color "yellow" "  Khởi động lại MySQL trên Windows/macOS"
    fi
fi

if [ -z "$CONTAINERS" ]; then
    print_color "yellow" "- AI Service: Khởi động container"
    if [ "$ENVIRONMENT" == "linux" ]; then
        print_color "yellow" "  Khởi động: ./run-container.sh"
    else
        print_color "yellow" "  Khởi động: ./run-container.bat"
    fi
fi

echo "" 
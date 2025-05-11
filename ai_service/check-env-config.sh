#!/bin/bash

# Script kiểm tra cấu hình môi trường của các module AI Service
# Kiểm tra tất cả các file config.py

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

# Tiêu đề
print_color "blue" "===== MAGAZINE AI SYSTEM - KIỂM TRA CẤU HÌNH ====="
echo ""

# Mảng chứa đường dẫn tới các file config.py
CONFIG_FILES=(
    "scraper/config.py"
    "ai_service/rewrite/config.py"
    "ai_service/keyword_rewrite/config.py"
    "ai_service/facebook_scraper/config.py"
    "ai_service/facebook_rewrite/config.py"
)

# Phát hiện hệ điều hành
print_color "yellow" "Đang phát hiện môi trường..."

if grep -q Microsoft /proc/version || grep -q WSL /proc/version; then
    print_color "green" "Phát hiện WSL/Windows environment"
    EXPECTED_HOST_IP="host.docker.internal"
elif [ "$(uname)" == "Darwin" ]; then
    print_color "green" "Phát hiện macOS environment"
    EXPECTED_HOST_IP="host.docker.internal"
else
    print_color "green" "Phát hiện Linux environment"
    EXPECTED_HOST_IP="172.17.0.1"
fi

print_color "green" "Host IP mong đợi: $EXPECTED_HOST_IP"
echo ""

# Kiểm tra từng file config.py
print_color "yellow" "Kiểm tra các file config.py..."
echo ""

for file in "${CONFIG_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_color "blue" "Đang kiểm tra: $file"
        
        # Kiểm tra chức năng phát hiện môi trường
        if grep -q "IN_DOCKER = os.path.exists('/.dockerenv')" "$file"; then
            print_color "green" "✓ Phát hiện Docker container"
        else
            print_color "red" "✗ Không tìm thấy chức năng phát hiện Docker container"
        fi
        
        # Kiểm tra chức năng phát hiện Linux
        if grep -q "if os.path.exists('/etc/os-release')" "$file" || grep -q "if os.path.isfile('/etc/os-release')" "$file"; then
            print_color "green" "✓ Phát hiện OS Linux"
        else
            print_color "red" "✗ Không tìm thấy chức năng phát hiện OS Linux"
        fi
        
        # Kiểm tra BACKEND_URL và DB_HOST
        if grep -q "BACKEND_URL = \"http://172.17.0.1\"" "$file" || grep -q "BACKEND_URL = http://172.17.0.1" "$file" || grep -q "BACKEND_URL = \"http://host.docker.internal\"" "$file"; then
            print_color "green" "✓ Cấu hình BACKEND_URL"
        else
            print_color "red" "✗ Không tìm thấy cấu hình BACKEND_URL phù hợp"
        fi
        
        if grep -q "DB_HOST = \"172.17.0.1\"" "$file" || grep -q "DB_HOST = 172.17.0.1" "$file" || grep -q "DB_HOST = \"host.docker.internal\"" "$file"; then
            print_color "green" "✓ Cấu hình DB_HOST"
        else
            print_color "red" "✗ Không tìm thấy cấu hình DB_HOST phù hợp"
        fi
        
        echo ""
    else
        print_color "red" "✗ Không tìm thấy file: $file"
        echo ""
    fi
done

# Kiểm tra file .env
print_color "blue" "Kiểm tra file .env"
if [ -f ".env" ]; then
    # Kiểm tra BACKEND_URL
    if grep -q "BACKEND_URL=.*$EXPECTED_HOST_IP" ".env"; then
        print_color "green" "✓ BACKEND_URL được cấu hình đúng trong .env"
    else
        print_color "red" "✗ BACKEND_URL không được cấu hình đúng trong .env"
    fi
    
    # Kiểm tra DB_HOST
    if grep -q "DB_HOST=.*$EXPECTED_HOST_IP" ".env"; then
        print_color "green" "✓ DB_HOST được cấu hình đúng trong .env"
    else
        print_color "red" "✗ DB_HOST không được cấu hình đúng trong .env"
    fi
    
    # Kiểm tra OLLAMA_HOST
    if grep -q "OLLAMA_HOST=.*$EXPECTED_HOST_IP:11434" ".env"; then
        print_color "green" "✓ OLLAMA_HOST được cấu hình đúng trong .env"
    else
        print_color "red" "✗ OLLAMA_HOST không được cấu hình đúng trong .env"
    fi
    
    echo ""
else
    print_color "red" "✗ Không tìm thấy file .env"
    echo ""
fi

# Kết luận
print_color "blue" "===== KẾT QUẢ KIỂM TRA ====="
print_color "yellow" "Để cập nhật tự động cấu hình môi trường, hãy chạy:"
print_color "yellow" "  ./setup-environment.sh"
if [ "$EXPECTED_HOST_IP" == "host.docker.internal" ]; then
    print_color "yellow" "Hoặc trên Windows: setup-environment.bat"
fi
echo "" 
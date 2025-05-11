#!/bin/bash

# Script tự động cấu hình môi trường cho dự án Magazine AI System
# Hỗ trợ cả Windows và Linux

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
print_color "blue" "===== MAGAZINE AI SYSTEM - THIẾT LẬP MÔI TRƯỜNG ====="
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

# Cập nhật file .env của AI Service
print_color "yellow" "Cập nhật file .env cho AI Service..."

# Tạo file .env từ template
if [ -f ".env" ]; then
    cp .env .env.backup
    print_color "green" "Đã tạo bản sao lưu .env.backup"
fi

# Cập nhật các giá trị cấu hình
if [ "$ENVIRONMENT" == "linux" ]; then
    print_color "yellow" "Đang cấu hình cho Linux (IP: $HOST_IP)..."
    
    # Cập nhật các giá trị trong file .env
    if [ -f ".env" ]; then
        sed -i "s/DB_HOST=.*/DB_HOST=$HOST_IP/g" .env
        sed -i "s|BACKEND_URL=.*|BACKEND_URL=http://$HOST_IP|g" .env
        
        # Kiểm tra và cập nhật OLLAMA_HOST
        if grep -q "OLLAMA_HOST=" .env; then
            sed -i "s|OLLAMA_HOST=.*|OLLAMA_HOST=http://$HOST_IP:11434|g" .env
        else
            echo "OLLAMA_HOST=http://$HOST_IP:11434" >> .env
        fi
        
        # Kiểm tra và cập nhật OLLAMA_BASE_URL
        if grep -q "OLLAMA_BASE_URL=" .env; then
            sed -i "s|OLLAMA_BASE_URL=.*|OLLAMA_BASE_URL=http://$HOST_IP:11434|g" .env
        else
            echo "OLLAMA_BASE_URL=http://$HOST_IP:11434" >> .env
        fi
        
        print_color "green" "Đã cập nhật file .env với $HOST_IP!"
    else
        print_color "red" "Không tìm thấy file .env. Hãy tạo file .env trước."
    fi
else
    print_color "yellow" "Đang cấu hình cho Windows/macOS (IP: $HOST_IP)..."
    
    # Cập nhật các giá trị trong file .env
    if [ -f ".env" ]; then
        sed -i "s/DB_HOST=.*/DB_HOST=$HOST_IP/g" .env
        sed -i "s|BACKEND_URL=.*|BACKEND_URL=http://$HOST_IP|g" .env
        
        # Kiểm tra và cập nhật OLLAMA_HOST
        if grep -q "OLLAMA_HOST=" .env; then
            sed -i "s|OLLAMA_HOST=.*|OLLAMA_HOST=http://$HOST_IP:11434|g" .env
        else
            echo "OLLAMA_HOST=http://$HOST_IP:11434" >> .env
        fi
        
        # Kiểm tra và cập nhật OLLAMA_BASE_URL
        if grep -q "OLLAMA_BASE_URL=" .env; then
            sed -i "s|OLLAMA_BASE_URL=.*|OLLAMA_BASE_URL=http://$HOST_IP:11434|g" .env
        else
            echo "OLLAMA_BASE_URL=http://$HOST_IP:11434" >> .env
        fi
        
        print_color "green" "Đã cập nhật file .env với $HOST_IP!"
    else
        print_color "red" "Không tìm thấy file .env. Hãy tạo file .env trước."
    fi
fi

# Chỉnh sửa run-container script
if [ "$ENVIRONMENT" == "linux" ]; then
    print_color "yellow" "Đang cập nhật run-container.sh..."
    if [ -f "run-container.sh" ]; then
        # Đảm bảo script có quyền thực thi
        chmod +x run-container.sh
        print_color "green" "Script run-container.sh đã được cấu hình cho Linux."
    else
        print_color "red" "Không tìm thấy file run-container.sh!"
    fi
else
    print_color "yellow" "Đang kiểm tra run-container.bat..."
    if [ -f "run-container.bat" ]; then
        print_color "green" "Script run-container.bat đã tồn tại cho Windows."
    else
        print_color "red" "Không tìm thấy file run-container.bat!"
    fi
fi

# Kiểm tra sẵn sàng
echo ""
print_color "blue" "===== KIỂM TRA TRẠNG THÁI DOCKER ====="
if command -v docker &> /dev/null; then
    print_color "green" "✓ Docker được cài đặt"
    
    # Kiểm tra Docker daemon
    if docker info &> /dev/null; then
        print_color "green" "✓ Docker daemon đang chạy"
    else
        print_color "red" "✗ Docker daemon không chạy"
        print_color "yellow" "Vui lòng khởi động Docker daemon trước khi tiếp tục"
    fi
else
    print_color "red" "✗ Docker chưa được cài đặt"
    print_color "yellow" "Vui lòng cài đặt Docker trước khi tiếp tục"
fi

# Kết luận
echo ""
print_color "blue" "===== CẤU HÌNH HOÀN TẤT ====="
print_color "green" "Môi trường đã được thiết lập thành công cho $ENVIRONMENT!"
print_color "green" "Host IP: $HOST_IP"
echo ""
print_color "yellow" "Để chạy container:"
if [ "$ENVIRONMENT" == "linux" ]; then
    print_color "yellow" "  ./run-container.sh"
else
    print_color "yellow" "  ./run-container.bat"
fi
echo "" 
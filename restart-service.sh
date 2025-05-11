#!/bin/bash

# Script dừng và khởi động lại container AI service

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
print_color "blue" "===== MAGAZINE AI SYSTEM - KHỞI ĐỘNG LẠI CONTAINER ====="
echo ""

# Kiểm tra Docker
if ! command -v docker &> /dev/null; then
    print_color "red" "✗ Docker không được cài đặt"
    print_color "yellow" "Vui lòng cài đặt Docker trước khi tiếp tục"
    exit 1
fi

# Kiểm tra Docker daemon
if ! docker info &> /dev/null; then
    print_color "red" "✗ Docker daemon không chạy"
    print_color "yellow" "Vui lòng khởi động Docker daemon trước khi tiếp tục"
    exit 1
fi

# Tìm container AI service
print_color "yellow" "Tìm container AI service..."
CONTAINER_ID=$(docker ps -a --filter "name=ai-service" --format "{{.ID}}")

if [ -z "$CONTAINER_ID" ]; then
    print_color "red" "✗ Không tìm thấy container AI service"
    print_color "yellow" "Vui lòng chạy run-container.sh để khởi động container trước"
    exit 1
fi

# Dừng container AI service
print_color "yellow" "Dừng container AI service..."
docker stop $CONTAINER_ID
if [ $? -eq 0 ]; then
    print_color "green" "✓ Đã dừng container AI service"
else
    print_color "red" "✗ Không thể dừng container AI service"
    exit 1
fi

# Khởi động lại container AI service
print_color "yellow" "Khởi động lại container AI service..."
docker start $CONTAINER_ID
if [ $? -eq 0 ]; then
    print_color "green" "✓ Đã khởi động lại container AI service"
else
    print_color "red" "✗ Không thể khởi động lại container AI service"
    exit 1
fi

# Hiển thị log container
print_color "yellow" "Hiển thị log container..."
docker logs --tail 20 $CONTAINER_ID

# Kết luận
echo ""
print_color "blue" "===== KHỞI ĐỘNG LẠI THÀNH CÔNG ====="
print_color "green" "Container AI service đã được khởi động lại thành công!"
print_color "yellow" "Để kiểm tra trạng thái container: docker ps"
print_color "yellow" "Để xem log container: docker logs $CONTAINER_ID"
echo "" 
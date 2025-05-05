#!/bin/bash

echo "=== Xóa image cũ nếu tồn tại ==="
if docker images | grep -q "ai-service:optimized"; then
  docker rmi ai-service:optimized
fi

# Build Docker image với Dockerfile đã tối ưu
echo "=== Bắt đầu build image tối ưu ==="
docker build -t ai-service:optimized -f optimized-dockerfile .

# Hiển thị thông tin về kích thước của image
echo "=== Kích thước của docker image đã tối ưu ==="
docker images | grep ai-service

# Kiểm tra kích thước của từng layer
echo "=== Kích thước của từng layer trong image đã tối ưu ==="
docker history ai-service:optimized --human

# So sánh với image cũ nếu có
if docker images | grep -q "ai-service.*55025"; then
  echo "=== So sánh với image cũ ==="
  echo "Image cũ:"
  docker images | grep "ai-service.*55025"
  echo "Image mới đã tối ưu:"
  docker images | grep "ai-service:optimized"
  
  # Tính toán phần trăm giảm kích thước
  OLD_SIZE=$(docker images --format "{{.Size}}" ai-service:55025 | sed 's/GB//')
  NEW_SIZE=$(docker images --format "{{.Size}}" ai-service:optimized | sed 's/GB//')
  
  if [ ! -z "$OLD_SIZE" ] && [ ! -z "$NEW_SIZE" ]; then
    echo "Kích thước giảm: $OLD_SIZE GB -> $NEW_SIZE GB"
    DIFF=$(echo "$OLD_SIZE - $NEW_SIZE" | bc)
    PERCENT=$(echo "($DIFF / $OLD_SIZE) * 100" | bc -l)
    echo "Giảm khoảng $(printf "%.2f" $PERCENT)% kích thước"
  fi
fi 
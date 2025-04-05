#!/bin/bash
#
# Script triển khai dịch vụ AI Keyword Rewrite ở chế độ daemon
#

SERVICE_DIR="$(dirname "$(readlink -f "$0")")"
VENV_DIR="$SERVICE_DIR/venv"
LOG_FILE="$SERVICE_DIR/ai_service.log"

echo "=== AI Keyword Rewrite Service Deployment ==="
echo "Service directory: $SERVICE_DIR"

# Kiểm tra và tạo môi trường ảo Python nếu cần
if [ ! -d "$VENV_DIR" ]; then
    echo "Creating Python virtual environment..."
    python3 -m venv "$VENV_DIR"
    source "$VENV_DIR/bin/activate"
    echo "Installing dependencies..."
    pip install -r "$SERVICE_DIR/requirements.txt"
else
    echo "Using existing virtual environment..."
    source "$VENV_DIR/bin/activate"
fi

# Kiểm tra Ollama
echo "Checking Ollama..."
if ! curl -s http://localhost:11434/api/version > /dev/null; then
    echo "WARNING: Ollama không đang chạy hoặc không thể kết nối!"
    echo "Hãy đảm bảo Ollama đã được cài đặt và đang chạy trước khi tiếp tục."
    echo "Tải Ollama tại: https://ollama.com/download"
    read -p "Tiếp tục mà không có Ollama? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Đã hủy triển khai. Vui lòng cài đặt Ollama trước."
        exit 1
    fi
fi

# Kiểm tra mô hình
MODEL_NAME=$(grep "OLLAMA_MODEL" "$SERVICE_DIR/.env" | cut -d '=' -f2)
if [ -z "$MODEL_NAME" ]; then
    MODEL_NAME="gemma2:latest"
fi

echo "Checking model: $MODEL_NAME..."
if curl -s http://localhost:11434/api/version > /dev/null; then
    if ! curl -s -H "Content-Type: application/json" -d "{\"name\":\"$MODEL_NAME\"}" http://localhost:11434/api/show > /dev/null; then
        echo "WARNING: Mô hình $MODEL_NAME chưa được cài đặt!"
        read -p "Tải mô hình $MODEL_NAME ngay bây giờ? (y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            echo "Đang tải mô hình $MODEL_NAME... (có thể mất nhiều thời gian)"
            curl -X POST http://localhost:11434/api/pull -d "{\"name\":\"$MODEL_NAME\"}"
            echo "Hoàn tất tải mô hình!"
        fi
    else
        echo "Mô hình $MODEL_NAME đã sẵn sàng."
    fi
fi

# Dừng dịch vụ cũ nếu đang chạy
echo "Checking for existing service..."
PID=$(ps -ef | grep "python.*api.py" | grep -v grep | awk '{print $2}')
if [ ! -z "$PID" ]; then
    echo "Found existing service with PID $PID. Stopping..."
    kill $PID
    sleep 2
fi

# Bắt đầu dịch vụ ở chế độ daemon
echo "Starting AI Keyword Rewrite Service..."
nohup python "$SERVICE_DIR/api.py" > "$LOG_FILE" 2>&1 &

# Lưu PID
echo $! > "$SERVICE_DIR/service.pid"
echo "Service started with PID $!"
echo "Log file: $LOG_FILE"

# Kiểm tra dịch vụ đã chạy chưa
sleep 3
if curl -s http://localhost:5000/health > /dev/null; then
    echo "Service is running successfully!"
    echo "Health check endpoint: http://localhost:5000/health"
    echo "API endpoint: http://localhost:5000/api/keyword_rewrite/process"
else
    echo "WARNING: Service may not have started correctly!"
    echo "Check the log file for details: $LOG_FILE"
fi

echo "=== Deployment Complete ===" 
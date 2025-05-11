#!/bin/bash
echo "===== Chay du an AI Service voi cong 55025 ====="
echo

echo "Dang build image ai-service:55025 tu Dockerfile..."
docker build -t ai-service:55025 -f Dockerfile .
if [ $? -ne 0 ]; then
    echo "Loi khi build image!"
    exit 1
fi

echo "Dang kiem tra xem container da ton tai..."
if docker ps -a | grep -q "ai-service-all"; then
    echo "Container ai-service-all da ton tai, dang dung va xoa..."
    docker stop ai-service-all > /dev/null
    docker rm ai-service-all > /dev/null
fi

echo
echo "=== Dang chay container ==="
docker run -d --name ai-service-all \
  -p 55025:55025 \
  -e BACKEND_URL=http://172.17.0.1 \
  -e BACKEND_PORT=80 \
  -e DB_HOST=172.17.0.1 \
  -e OLLAMA_HOST=http://172.17.0.1:11434 \
  -e OLLAMA_BASE_URL=http://172.17.0.1:11434 \
  ai-service:55025

if [ $? -ne 0 ]; then
    echo "Error starting container!"
    exit 1
fi

echo
echo "=== Container dang chay ==="
docker ps | grep "ai-service-all"

echo
echo "Cac endpoint co the truy cap:"
echo "- http://localhost:55025/ (Trang chu)"
echo "- http://localhost:55025/health (Kiem tra trang thai)"
echo "- http://localhost:55025/scraper/ (Scraper API)"
echo "- http://localhost:55025/rewrite/ (Rewrite API)"
echo "- http://localhost:55025/keyword-rewrite/ (Keyword Rewrite API)"
echo "- http://localhost:55025/facebook-scraper/ (Facebook Scraper API)"
echo "- http://localhost:55025/facebook-rewrite/ (Facebook Rewrite API)"
echo

echo "De xem logs, su dung: docker logs ai-service-all"
echo
@echo off
echo Service keyword_rewrite đã được khởi động trong Docker container.
echo API endpoint: http://localhost:5003
echo Endpoint /health: http://localhost:5003/health
echo Endpoint API: http://localhost:5003/api/keyword_rewrite/process
echo.
echo Nếu service chưa chạy, hãy sử dụng docker-compose trong thư mục ai_service:
echo docker-compose up -d keyword_rewrite
echo.
exit /b 0 
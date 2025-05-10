@echo off
echo ===== Chay du an AI Service voi cong 55025 =====
echo.

echo Dang build image ai-service:55025 tu optimized-dockerfile...
docker build -t ai-service:55025 -f Dockerfile .
if %ERRORLEVEL% NEQ 0 (
    echo Loi khi build image!
    pause
    exit /b %ERRORLEVEL%
)

echo Dang kiem tra xem container da ton tai...
docker ps -a | findstr "ai-service-all" >nul
if %ERRORLEVEL% EQU 0 (
    echo Container ai-service-all da ton tai, dang dung va xoa...
    docker stop ai-service-all >nul
    docker rm ai-service-all >nul
)

echo.
echo === Dang chay container ===
docker run -d --name ai-service-all ^
  -p 55025:55025 ^
  -e BACKEND_URL=http://host.docker.internal ^
  -e DB_HOST=host.docker.internal ^
  -e OLLAMA_HOST=http://host.docker.internal:11434 ^
  -e OLLAMA_BASE_URL=http://host.docker.internal:11434 ^
  ai-service:55025

if %ERRORLEVEL% NEQ 0 (
    echo Error starting container!
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo === Container dang chay ===
docker ps | findstr "ai-service-all"

echo.
echo Cac endpoint co the truy cap:
echo - http://localhost:55025/ (Trang chu)
echo - http://localhost:55025/health (Kiem tra trang thai)
echo - http://localhost:55025/scraper/ (Scraper API)
echo - http://localhost:55025/rewrite/ (Rewrite API)
echo - http://localhost:55025/keyword-rewrite/ (Keyword Rewrite API)
echo - http://localhost:55025/facebook-scraper/ (Facebook Scraper API)
echo - http://localhost:55025/facebook-rewrite/ (Facebook Rewrite API)
echo.

echo De xem logs, su dung: docker logs ai-service-all
echo.

pause
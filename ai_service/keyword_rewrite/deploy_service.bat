@echo off
echo === AI Keyword Rewrite Service Deployment ===
set SERVICE_DIR=%~dp0
echo Service directory: %SERVICE_DIR%

:: Kiểm tra và tạo môi trường ảo Python nếu cần
if not exist "%SERVICE_DIR%venv\" (
    echo Creating Python virtual environment...
    python -m venv "%SERVICE_DIR%venv"
    call "%SERVICE_DIR%venv\Scripts\activate.bat"
    echo Installing dependencies...
    pip install -r "%SERVICE_DIR%requirements.txt"
) else (
    echo Using existing virtual environment...
    call "%SERVICE_DIR%venv\Scripts\activate.bat"
)

:: Kiểm tra Ollama đang chạy
echo Checking Ollama...
curl -s http://localhost:11434/api/version > nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo WARNING: Ollama khong dang chay hoac khong the ket noi!
    echo Hay dam bao Ollama da duoc cai dat va dang chay truoc khi tiep tuc.
    echo Tai Ollama tai: https://ollama.com/download
    set /p CONTINUE="Tiep tuc ma khong co Ollama? (y/n) "
    if /i not "%CONTINUE%"=="y" (
        echo Da huy trien khai. Vui long cai dat Ollama truoc.
        exit /b 1
    )
)

:: Kiểm tra mô hình
for /f "tokens=2 delims==" %%a in ('type "%SERVICE_DIR%.env" ^| findstr "OLLAMA_MODEL"') do set MODEL_NAME=%%a
if "%MODEL_NAME%"=="" set MODEL_NAME=gemma2:latest

echo Checking model: %MODEL_NAME%...
curl -s http://localhost:11434/api/version > nul 2>&1
if %ERRORLEVEL% EQU 0 (
    curl -s -H "Content-Type: application/json" -d "{\"name\":\"%MODEL_NAME%\"}" http://localhost:11434/api/show > nul 2>&1
    if %ERRORLEVEL% NEQ 0 (
        echo WARNING: Mo hinh %MODEL_NAME% chua duoc cai dat!
        set /p DOWNLOAD="Tai mo hinh %MODEL_NAME% ngay bay gio? (y/n) "
        if /i "%DOWNLOAD%"=="y" (
            echo Dang tai mo hinh %MODEL_NAME%... (co the mat nhieu thoi gian)
            curl -X POST http://localhost:11434/api/pull -d "{\"name\":\"%MODEL_NAME%\"}"
            echo Hoan tat tai mo hinh!
        )
    ) else (
        echo Mo hinh %MODEL_NAME% da san sang.
    )
)

:: Dừng dịch vụ cũ nếu đang chạy
echo Checking for existing service...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr "0.0.0.0:5000"') do set PID=%%a
if not "%PID%"=="" (
    echo Found existing service with PID %PID%. Stopping...
    taskkill /F /PID %PID%
    timeout /t 2 /nobreak > nul
)

:: Bắt đầu dịch vụ ở chế độ daemon
echo Starting AI Keyword Rewrite Service...
start /B pythonw "%SERVICE_DIR%api.py" > "%SERVICE_DIR%ai_service.log" 2>&1

:: Đợi một chút để dịch vụ khởi động
timeout /t 3 /nobreak > nul

:: Kiểm tra dịch vụ đã chạy chưa
curl -s http://localhost:5000/health > nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo Service is running successfully!
    echo Health check endpoint: http://localhost:5000/health
    echo API endpoint: http://localhost:5000/api/keyword_rewrite/process
) else (
    echo WARNING: Service may not have started correctly!
    echo Check the log file for details: %SERVICE_DIR%ai_service.log
)

echo === Deployment Complete === 
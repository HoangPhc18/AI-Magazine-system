@echo off
:: AI Magazine Scraper Runner
:: Chay module scraper trong moi truong ao Python

set BASE_DIR=%~dp0
set MODULE_DIR=%BASE_DIR%scraper
set VENV_DIR=%BASE_DIR%venv
set OUTPUT_DIR=%MODULE_DIR%\output
set ENV_FILE=%MODULE_DIR%\.env

echo === AI Magazine Scraper ===
echo Thoi gian bat dau: %date% %time%
echo.

:: Kiem tra moi truong ao da ton tai chua
if not exist "%VENV_DIR%\Scripts\activate.bat" (
    echo [ERROR] Moi truong ao chua duoc tao. Hay chay setup_venv.bat truoc.
    pause
    exit /b 1
)

:: Dam bao thu muc output ton tai
if not exist "%OUTPUT_DIR%" (
    mkdir "%OUTPUT_DIR%"
    echo [INFO] Da tao thu muc output.
)

:: Kiem tra file .env ton tai
if not exist "%ENV_FILE%" (
    echo [WARNING] Khong tim thay file .env. Dang tao file mau...
    echo # API Keys > "%ENV_FILE%"
    echo WORLDNEWS_API_KEY=your_worldnews_api_key_here >> "%ENV_FILE%"
    echo CURRENTS_API_KEY=your_currents_api_key_here >> "%ENV_FILE%"
    echo [WARNING] Hay cap nhat API keys trong file %ENV_FILE% truoc khi chay lai.
    pause
    exit /b 1
)

:: Kich hoat moi truong ao
call "%VENV_DIR%\Scripts\activate.bat"
echo [INFO] Da kich hoat moi truong ao Python.

:: Xoa file google_search_response.html cu hon 1 ngay
echo [INFO] Dang don dep files cu...
forfiles /P "%MODULE_DIR%" /M "google_search_response.html" /D -1 /C "cmd /c del @path" 2>nul
echo [INFO] Da hoan thanh don dep.

:: Thiet lap bien moi truong de cac scripts con biet la tu bat
set AUTO_SEND=true

:: Chuyen den thu muc scraper
cd /d "%MODULE_DIR%"

:: Thong bao gioi han so bai viet
echo [INFO] Moi API se lay 1 bai viet cho moi danh muc.

:: Chay script chinh voi tu dong gui den backend
echo [INFO] Dang chay scraper...
python main.py --auto-send --retention-days 3 --batch-size 5 --verbose

echo.
echo [INFO] Da hoan thanh scraper.
echo Thoi gian ket thuc: %date% %time%
echo.

:: Huy kich hoat moi truong ao
call deactivate
pause 
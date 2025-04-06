@echo off
:: AI Magazine Keyword Rewriter Runner
:: Chay module keyword_rewrite trong moi truong ao Python

set BASE_DIR=%~dp0
set MODULE_DIR=%BASE_DIR%keyword_rewrite
set VENV_DIR=%BASE_DIR%venv

echo === AI Magazine Keyword Rewriter Service ===
echo Thoi gian bat dau: %date% %time%
echo.

:: Kiem tra moi truong ao da ton tai chua
if not exist "%VENV_DIR%\Scripts\activate.bat" (
    echo [ERROR] Moi truong ao chua duoc tao. Hay chay setup_venv.bat truoc.
    pause
    exit /b 1
)

:: Kich hoat moi truong ao
call "%VENV_DIR%\Scripts\activate.bat"
echo [INFO] Da kich hoat moi truong ao Python.

:: Xoa log files va HTML cu hon 1 ngay
echo [INFO] Dang don dep files cu...
forfiles /P "%MODULE_DIR%" /M "*.log" /D -1 /C "cmd /c del @path" 2>nul
forfiles /P "%MODULE_DIR%" /M "google_search_response.html" /D -1 /C "cmd /c del @path" 2>nul
echo [INFO] Da hoan thanh don dep.

:: Chuyen den thu muc keyword_rewrite
cd /d "%MODULE_DIR%"

:: Chay script chinh API
echo [INFO] Dang khoi dong dich vu keyword_rewrite...
echo [INFO] Nhan Ctrl+C de dung dich vu.
python api.py

echo.
echo [INFO] Da dung dich vu keyword_rewrite.
echo Thoi gian ket thuc: %date% %time%
echo.

:: Huy kich hoat moi truong ao
call deactivate
pause 
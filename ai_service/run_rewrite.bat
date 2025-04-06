@echo off
:: AI Magazine Rewriter Runner
:: Chay module rewrite trong moi truong ao Python

set BASE_DIR=%~dp0
set MODULE_DIR=%BASE_DIR%rewrite
set VENV_DIR=%BASE_DIR%venv

echo === AI Magazine Rewriter ===
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

:: Xoa log files cu hon 1 ngay
echo [INFO] Dang don dep files cu...
forfiles /P "%MODULE_DIR%" /M "*.log" /D -1 /C "cmd /c del @path" 2>nul
echo [INFO] Da hoan thanh don dep.

:: Chuyen den thu muc rewrite
cd /d "%MODULE_DIR%"

:: Chay script chinh
echo [INFO] Dang chay rewriter...
python rewrite_from_db.py --auto --limit 3 --delete --log rewriter.log

echo.
echo [INFO] Da hoan thanh rewrite.
echo Thoi gian ket thuc: %date% %time%
echo.

:: Huy kich hoat moi truong ao
call deactivate
pause 
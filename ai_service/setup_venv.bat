@echo off
echo === AI Service - Cai dat Moi truong Ao Python ===
set BASE_DIR=%~dp0
cd /d %BASE_DIR%

:: Kiem tra xem Python da duoc cai dat chua
python --version > nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Python chua duoc cai dat hoac khong co trong PATH!
    echo Vui long cai dat Python tu https://www.python.org/downloads/
    pause
    exit /b 1
)

:: Kiem tra va tao moi truong ao
if not exist "%BASE_DIR%venv\" (
    echo [INFO] Dang tao moi truong ao Python...
    python -m venv venv
    if %ERRORLEVEL% NEQ 0 (
        echo [ERROR] Khong the tao moi truong ao Python!
        pause
        exit /b 1
    )
    echo [OK] Da tao moi truong ao thanh cong.
) else (
    echo [INFO] Da tim thay moi truong ao san co.
)

:: Kich hoat moi truong ao
call "%BASE_DIR%venv\Scripts\activate.bat"
echo [INFO] Da kich hoat moi truong ao Python.

:: Nang cap pip
echo [INFO] Nang cap pip...
python -m pip install --upgrade pip

:: Cai dat cac goi phu thuoc cho tung module
echo [INFO] Cai dat cac goi phu thuoc cho module...

if exist "%BASE_DIR%scraper\requirements.txt" (
    echo [INFO] Cai dat goi phu thuoc cho module scraper...
    pip install -r "%BASE_DIR%scraper\requirements.txt"
)

if exist "%BASE_DIR%rewrite\requirements.txt" (
    echo [INFO] Cai dat goi phu thuoc cho module rewrite...
    pip install -r "%BASE_DIR%rewrite\requirements.txt"
)

if exist "%BASE_DIR%keyword_rewrite\requirements.txt" (
    echo [INFO] Cai dat goi phu thuoc cho module keyword_rewrite...
    pip install -r "%BASE_DIR%keyword_rewrite\requirements.txt"
)

echo.
echo === Moi truong Python da san sang ===
echo.
echo De chay cac module, su dung cac lenh sau:
echo - run_scraper.bat         : Chay module scraper
echo - run_rewrite.bat         : Chay module rewrite
echo - run_keyword_rewrite.bat : Chay dich vu keyword_rewrite
echo.
echo [INFO] Moi truong hien tai: (venv)
echo.

:: Hien thi trang thai hien tai cua Python
python --version
pip --version
where python

pause 
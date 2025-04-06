@echo off
:: AI Magazine - Quan ly tat ca dich vu tu dong
:: File nay se cai dat cac scheduled task de tu dong khoi dong cac module sau khi may tinh bat
:: va lap lai cac module theo lich dinh san

setlocal enabledelayedexpansion

set BASE_DIR=%~dp0
set VENV_DIR=%BASE_DIR%venv
set TASK_PREFIX=AIMagazine_

echo === AI Magazine - Cai dat Lich trinh Tu dong ===
echo Thoi gian cai dat: %date% %time%
echo.

:: Kiem tra quyen admin
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Ban can chay bat voi quyen Administrator de cai dat task!
    echo Hay dong cua so nay, nhan chuot phai vao file run_all_services.bat
    echo va chon "Run as administrator".
    pause
    exit /b 1
)

:: Xoa cac task cu neu co
echo [INFO] Dang xoa cac task cu (neu co)...
schtasks /query /tn "%TASK_PREFIX%Setup" >nul 2>&1
if %errorlevel% equ 0 schtasks /delete /tn "%TASK_PREFIX%Setup" /f >nul 2>&1

schtasks /query /tn "%TASK_PREFIX%Scraper" >nul 2>&1
if %errorlevel% equ 0 schtasks /delete /tn "%TASK_PREFIX%Scraper" /f >nul 2>&1

schtasks /query /tn "%TASK_PREFIX%Rewriter" >nul 2>&1
if %errorlevel% equ 0 schtasks /delete /tn "%TASK_PREFIX%Rewriter" /f >nul 2>&1

schtasks /query /tn "%TASK_PREFIX%KeywordRewrite" >nul 2>&1
if %errorlevel% equ 0 schtasks /delete /tn "%TASK_PREFIX%KeywordRewrite" /f >nul 2>&1

:: Tao thu muc de chua cac helper vbs
set HELPER_DIR=%BASE_DIR%task_helpers
if not exist "%HELPER_DIR%" mkdir "%HELPER_DIR%"

:: Tao cac file VBS de chay ngam
echo [INFO] Tao cac file helper de chay ngam...

:: Helper cho setup_venv
echo Set WshShell = CreateObject("WScript.Shell") > "%HELPER_DIR%\run_setup_venv.vbs"
echo WshShell.Run Chr(34) ^& "%BASE_DIR%setup_venv.bat" ^& Chr(34), 0, False >> "%HELPER_DIR%\run_setup_venv.vbs"

:: Helper cho run_scraper
echo Set WshShell = CreateObject("WScript.Shell") > "%HELPER_DIR%\run_scraper.vbs"
echo WshShell.Run Chr(34) ^& "%BASE_DIR%run_scraper.bat" ^& Chr(34), 0, False >> "%HELPER_DIR%\run_scraper.vbs"

:: Helper cho run_rewrite
echo Set WshShell = CreateObject("WScript.Shell") > "%HELPER_DIR%\run_rewrite.vbs"
echo WshShell.Run Chr(34) ^& "%BASE_DIR%run_rewrite.bat" ^& Chr(34), 0, False >> "%HELPER_DIR%\run_rewrite.vbs"

:: Helper cho run_keyword_rewrite
echo Set WshShell = CreateObject("WScript.Shell") > "%HELPER_DIR%\run_keyword_rewrite.vbs"
echo WshShell.Run Chr(34) ^& "%BASE_DIR%run_keyword_rewrite.bat" ^& Chr(34), 0, False >> "%HELPER_DIR%\run_keyword_rewrite.vbs"

:: Cai dat cac scheduled task
echo [INFO] Dang cai dat cac task tu dong...

:: 1. Task Setup Venv - 5 phut sau khi khoi dong
echo [INFO] Cai dat task kiem tra moi truong ao (chay sau 5 phut)...
schtasks /create /tn "%TASK_PREFIX%Setup" /tr "%HELPER_DIR%\run_setup_venv.vbs" /sc onlogon /delay 0005:00 /f

:: 2. Task Scraper - 10 phut sau khi khoi dong, lap lai moi 2 gio
echo [INFO] Cai dat task Scraper (chay sau 10 phut, lap lai moi 2 gio)...
schtasks /create /tn "%TASK_PREFIX%Scraper" /tr "%HELPER_DIR%\run_scraper.vbs" /sc onlogon /delay 0010:00 /f
schtasks /change /tn "%TASK_PREFIX%Scraper" /ri 120 /du 23:59

:: 3. Task Rewriter - 20 phut sau khi khoi dong, lap lai moi 2 gio
echo [INFO] Cai dat task Rewriter (chay sau 20 phut, lap lai moi 2 gio)...
schtasks /create /tn "%TASK_PREFIX%Rewriter" /tr "%HELPER_DIR%\run_rewrite.vbs" /sc onlogon /delay 0020:00 /f
schtasks /change /tn "%TASK_PREFIX%Rewriter" /ri 120 /du 23:59

:: 4. Task Keyword Rewrite API - 5 phut sau khi khoi dong
echo [INFO] Cai dat task Keyword Rewrite API (chay sau 5 phut)...
schtasks /create /tn "%TASK_PREFIX%KeywordRewrite" /tr "%HELPER_DIR%\run_keyword_rewrite.vbs" /sc onlogon /delay 0005:00 /f

echo.
echo === Da cai dat xong tat ca task tu dong ===
echo Tat ca dich vu se duoc khoi dong tu dong theo lich trinh:
echo - Setup Venv        : 5 phut sau khi khoi dong
echo - Keyword Rewrite   : 5 phut sau khi khoi dong (chay den khi tat may)
echo - Scraper           : 10 phut sau khi khoi dong, lap lai moi 2 gio
echo - Rewriter          : 20 phut sau khi khoi dong, lap lai moi 2 gio
echo.
echo De xem danh sach cac task, mo Command Prompt va go:
echo   schtasks /query /fo list /v /tn "%TASK_PREFIX%*"
echo.
echo De xoa tat ca cac task, chay lai file nay voi tham so "uninstall":
echo   run_all_services.bat uninstall
echo.

:: Kiem tra xem co tham so uninstall khong
if "%1"=="uninstall" (
    echo [INFO] Dang xoa tat ca cac scheduled task...
    schtasks /delete /tn "%TASK_PREFIX%Setup" /f >nul 2>&1
    schtasks /delete /tn "%TASK_PREFIX%Scraper" /f >nul 2>&1
    schtasks /delete /tn "%TASK_PREFIX%Rewriter" /f >nul 2>&1
    schtasks /delete /tn "%TASK_PREFIX%KeywordRewrite" /f >nul 2>&1
    
    echo [INFO] Dang xoa cac file helper...
    if exist "%HELPER_DIR%" rmdir /s /q "%HELPER_DIR%"
    
    echo [OK] Da xoa tat ca cac task va file helper.
)

pause 
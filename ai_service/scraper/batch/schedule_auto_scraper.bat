@echo off
:: Schedule Auto Scraper for AI Magazine System
:: This script both sets up scheduled task and can be executed directly
:: Usage: schedule_auto_scraper.bat [install|run|startup|uninstall|custom]
::   - install: Creates a scheduled task to run daily at 2:00 AM
::   - startup: Creates a scheduled task to run at user logon with a delay
::   - custom: Creates a custom scheduled task with user-defined time
::   - run: Runs the scraper process immediately (default if no parameter)
::   - uninstall: Removes all scheduled tasks

setlocal enabledelayedexpansion

:: Define paths
set SCRIPT_PATH=%~dp0
set BASE_PATH=F:\magazine-ai-system\ai_service\scraper
set OUTPUT_DIR=%BASE_PATH%\output
set SCRAPER_SCRIPT=%BASE_PATH%\main.py
set RETENTION_DAYS=3
set STARTUP_DELAY_MINUTES=10

:: Create output directory if it doesn't exist
if not exist "%OUTPUT_DIR%" mkdir "%OUTPUT_DIR%"

:: Check command parameter
if "%1"=="install" goto install_task
if "%1"=="startup" goto install_startup
if "%1"=="custom" goto install_custom
if "%1"=="uninstall" goto uninstall_tasks
if "%1"=="run" goto run_scraper
if "%1"=="silent" goto run_silent

:: If we get here with no params, we're running the scraper
goto run_scraper

:install_custom
echo =====================================================
echo Setting up custom scheduled task for AI Magazine Scraper
echo =====================================================

:: Get user input for schedule type and time
set /p SCHEDULE_TYPE=Enter schedule type (MINUTE, HOURLY, DAILY, WEEKLY, MONTHLY, ONCE): 
set /p SCHEDULE_TIME=Enter time (for DAILY, WEEKLY, MONTHLY use HH:MM format, for MINUTE/HOURLY enter number): 

echo You selected: %SCHEDULE_TYPE% at %SCHEDULE_TIME%
set /p CONFIRM=Is this correct? (Y/N): 

if /i "%CONFIRM%" NEQ "Y" goto install_custom

:: Check if custom task already exists
schtasks /query /tn "AI Magazine Auto Scraper Custom" >nul 2>&1
if %errorlevel% == 0 (
    echo Custom task already exists, updating...
    schtasks /delete /tn "AI Magazine Auto Scraper Custom" /f >nul
)

:: Create VBS script if it doesn't exist
if not exist "%SCRIPT_PATH%\run_hidden.vbs" (
    echo Set WshShell = CreateObject("WScript.Shell") > "%SCRIPT_PATH%\run_hidden.vbs"
    echo WshShell.Run chr(34) ^& "%~f0" ^& chr(34) ^& " silent", 0, False >> "%SCRIPT_PATH%\run_hidden.vbs"
)

:: Create different types of schedules based on user input
if /i "%SCHEDULE_TYPE%"=="MINUTE" (
    schtasks /create /sc minute /mo %SCHEDULE_TIME% /tn "AI Magazine Auto Scraper Custom" /tr "wscript.exe \"%SCRIPT_PATH%\run_hidden.vbs\""
) else if /i "%SCHEDULE_TYPE%"=="HOURLY" (
    schtasks /create /sc hourly /mo %SCHEDULE_TIME% /tn "AI Magazine Auto Scraper Custom" /tr "wscript.exe \"%SCRIPT_PATH%\run_hidden.vbs\""
) else if /i "%SCHEDULE_TYPE%"=="DAILY" (
    schtasks /create /sc daily /tn "AI Magazine Auto Scraper Custom" /tr "wscript.exe \"%SCRIPT_PATH%\run_hidden.vbs\"" /st %SCHEDULE_TIME%
) else if /i "%SCHEDULE_TYPE%"=="WEEKLY" (
    set /p WEEKDAY=Enter day of week (MON,TUE,WED,THU,FRI,SAT,SUN): 
    schtasks /create /sc weekly /d %WEEKDAY% /tn "AI Magazine Auto Scraper Custom" /tr "wscript.exe \"%SCRIPT_PATH%\run_hidden.vbs\"" /st %SCHEDULE_TIME%
) else if /i "%SCHEDULE_TYPE%"=="MONTHLY" (
    set /p MONTHDAY=Enter day of month (1-31): 
    schtasks /create /sc monthly /d %MONTHDAY% /tn "AI Magazine Auto Scraper Custom" /tr "wscript.exe \"%SCRIPT_PATH%\run_hidden.vbs\"" /st %SCHEDULE_TIME%
) else if /i "%SCHEDULE_TYPE%"=="ONCE" (
    set /p SCHEDULE_DATE=Enter date (MM/DD/YYYY): 
    schtasks /create /sc once /sd %SCHEDULE_DATE% /tn "AI Magazine Auto Scraper Custom" /tr "wscript.exe \"%SCRIPT_PATH%\run_hidden.vbs\"" /st %SCHEDULE_TIME%
) else (
    echo Invalid schedule type.
    goto install_custom
)

if %errorlevel% == 0 (
    echo =====================================================
    echo Successfully set up custom schedule (%SCHEDULE_TYPE% at %SCHEDULE_TIME%)
    echo =====================================================
) else (
    echo =====================================================
    echo Error setting up schedule! Please run with administrator privileges
    echo =====================================================
)
goto end

:install_startup
echo =====================================================
echo Setting up startup task for AI Magazine Scraper
echo =====================================================

:: Check if startup task already exists
schtasks /query /tn "AI Magazine Auto Scraper Startup" >nul 2>&1
if %errorlevel% == 0 (
    echo Startup task already exists, updating...
    schtasks /delete /tn "AI Magazine Auto Scraper Startup" /f >nul
)

:: Create VBS script to run batch file invisibly
echo Set WshShell = CreateObject("WScript.Shell") > "%SCRIPT_PATH%\run_hidden.vbs"
echo WshShell.Run chr(34) ^& "%~f0" ^& chr(34) ^& " silent", 0, False >> "%SCRIPT_PATH%\run_hidden.vbs"

:: Create scheduled task to run at user logon with delay
schtasks /create /sc onlogon /tn "AI Magazine Auto Scraper Startup" /tr "wscript.exe \"%SCRIPT_PATH%\run_hidden.vbs\"" /delay 0%STARTUP_DELAY_MINUTES%:00

if %errorlevel% == 0 (
    echo =====================================================
    echo Successfully set up to run silently at startup with %STARTUP_DELAY_MINUTES% minute delay
    echo =====================================================
) else (
    echo =====================================================
    echo Error setting up startup schedule! Please run with administrator privileges
    echo =====================================================
)
goto end

:install_task
echo =====================================================
echo Setting up scheduled task for AI Magazine Scraper
echo =====================================================

:: Check if daily task already exists
schtasks /query /tn "AI Magazine Auto Scraper Daily" >nul 2>&1
if %errorlevel% == 0 (
    echo Daily task already exists, updating...
    schtasks /delete /tn "AI Magazine Auto Scraper Daily" /f >nul
)

:: Create VBS script if it doesn't exist
if not exist "%SCRIPT_PATH%\run_hidden.vbs" (
    echo Set WshShell = CreateObject("WScript.Shell") > "%SCRIPT_PATH%\run_hidden.vbs"
    echo WshShell.Run chr(34) ^& "%~f0" ^& chr(34) ^& " silent", 0, False >> "%SCRIPT_PATH%\run_hidden.vbs"
)

:: Create scheduled task to run daily at 2:00 AM (hidden mode)
schtasks /create /sc daily /tn "AI Magazine Auto Scraper Daily" /tr "wscript.exe \"%SCRIPT_PATH%\run_hidden.vbs\"" /st 02:00

if %errorlevel% == 0 (
    echo =====================================================
    echo Successfully set up automated schedule to run daily at 2:00 AM (hidden mode)
    echo =====================================================
) else (
    echo =====================================================
    echo Error setting up schedule! Please run with administrator privileges
    echo =====================================================
)
goto end

:uninstall_tasks
echo =====================================================
echo Removing all scheduled tasks for AI Magazine Scraper
echo =====================================================

:: Remove daily task if it exists
schtasks /query /tn "AI Magazine Auto Scraper Daily" >nul 2>&1
if %errorlevel% == 0 (
    schtasks /delete /tn "AI Magazine Auto Scraper Daily" /f
    echo Daily task removed.
) else (
    echo Daily task not found.
)

:: Remove startup task if it exists
schtasks /query /tn "AI Magazine Auto Scraper Startup" >nul 2>&1
if %errorlevel% == 0 (
    schtasks /delete /tn "AI Magazine Auto Scraper Startup" /f
    echo Startup task removed.
) else (
    echo Startup task not found.
)

:: Remove custom task if it exists
schtasks /query /tn "AI Magazine Auto Scraper Custom" >nul 2>&1
if %errorlevel% == 0 (
    schtasks /delete /tn "AI Magazine Auto Scraper Custom" /f
    echo Custom task removed.
) else (
    echo Custom task not found.
)

:: Remove VBS helper script if it exists
if exist "%SCRIPT_PATH%\run_hidden.vbs" (
    del "%SCRIPT_PATH%\run_hidden.vbs"
    echo Helper script removed.
)

echo =====================================================
echo All scheduled tasks have been removed.
echo =====================================================
goto end

:run_silent
:: This mode runs completely silently with no console window
goto run_background

:run_scraper
:: Run the scraper with console output
echo =====================================================
echo AI Magazine News Scraper - Starting data collection
echo Started at: %date% %time%
echo =====================================================
echo.

:run_background
:: Create log file with timestamp
set timestamp=%date:~6,4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set timestamp=!timestamp: =0!
set LOG_FILE=%OUTPUT_DIR%\auto_scraper_!timestamp!.log

:: Log to file only (silent mode won't show console output)
echo =====================================================>> "%LOG_FILE%"
echo AI Magazine News Scraper - Starting data collection>> "%LOG_FILE%"
echo Started at: %date% %time%>> "%LOG_FILE%"
echo =====================================================>> "%LOG_FILE%"
echo.>> "%LOG_FILE%"

cd /d "%BASE_PATH%"

echo 1. Running the scraper...>> "%LOG_FILE%"

:: Run the main script with auto-send, 3-day retention, batch size of 5, and verbose output
python "%SCRAPER_SCRIPT%" --auto-send --retention-days %RETENTION_DAYS% --batch-size 5 --verbose >> "%LOG_FILE%" 2>&1

echo.>> "%LOG_FILE%"
echo 2. Scraping completed!>> "%LOG_FILE%"
echo.>> "%LOG_FILE%"

:: Clean up old files (older than 3 days)
echo 3. Cleaning up files older than %RETENTION_DAYS% days...>> "%LOG_FILE%"

:: For Windows, use forfiles to delete files older than N days
forfiles /p "%OUTPUT_DIR%" /s /m "*.json" /d -%RETENTION_DAYS% /c "cmd /c del @path" 2>nul
forfiles /p "%OUTPUT_DIR%" /s /m "*.log" /d -%RETENTION_DAYS% /c "cmd /c del @path" 2>nul

echo =====================================================>> "%LOG_FILE%"
echo Finished at: %date% %time%>> "%LOG_FILE%"
echo =====================================================>> "%LOG_FILE%"

:: Log successful completion
echo %date% %time% - Auto scraper completed successfully >> "%OUTPUT_DIR%\auto_scraper_history.log"

if "%1"=="silent" exit /b

:end
if "%1"=="run" exit /b
if "%1"=="install" exit /b
if "%1"=="startup" exit /b
if "%1"=="custom" exit /b
if "%1"=="uninstall" exit /b
pause 
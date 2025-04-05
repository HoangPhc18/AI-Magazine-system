@echo off
echo Starting AI Keyword Rewrite Service...
cd /d %~dp0

:: Xóa các file log cũ hơn 1 ngày
echo Cleaning up old log files...
forfiles /P "%~dp0" /M "*.log" /D -1 /C "cmd /c del @path" 2>nul
forfiles /P "%~dp0" /M "*_2*.log" /D -1 /C "cmd /c del @path" 2>nul
forfiles /P "%~dp0" /M "google_search_response.html" /D -1 /C "cmd /c del @path" 2>nul
echo Cleanup completed.

:: Khởi động dịch vụ AI
python api.py 
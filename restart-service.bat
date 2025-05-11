@echo off
REM Script dừng và khởi động lại container AI service

setlocal enabledelayedexpansion

REM Hàm hiển thị màu sắc
call :PrintBlue "===== MAGAZINE AI SYSTEM - KHỞI ĐỘNG LẠI CONTAINER ====="
echo.

REM Kiểm tra Docker
where docker >nul 2>nul
if %errorlevel% neq 0 (
    call :PrintRed "✗ Docker không được cài đặt"
    call :PrintYellow "Vui lòng cài đặt Docker Desktop trước khi tiếp tục"
    exit /b 1
)

REM Kiểm tra Docker daemon
docker info >nul 2>nul
if %errorlevel% neq 0 (
    call :PrintRed "✗ Docker daemon không chạy"
    call :PrintYellow "Vui lòng khởi động Docker Desktop trước khi tiếp tục"
    exit /b 1
)

REM Tìm container AI service
call :PrintYellow "Tìm container AI service..."
for /f "tokens=*" %%i in ('docker ps -a --filter "name=ai-service" --format "{{.ID}}"') do (
    set "CONTAINER_ID=%%i"
)

if "!CONTAINER_ID!"=="" (
    call :PrintRed "✗ Không tìm thấy container AI service"
    call :PrintYellow "Vui lòng chạy run-container.bat để khởi động container trước"
    exit /b 1
)

REM Dừng container AI service
call :PrintYellow "Dừng container AI service..."
docker stop !CONTAINER_ID!
if %errorlevel% equ 0 (
    call :PrintGreen "✓ Đã dừng container AI service"
) else (
    call :PrintRed "✗ Không thể dừng container AI service"
    exit /b 1
)

REM Khởi động lại container AI service
call :PrintYellow "Khởi động lại container AI service..."
docker start !CONTAINER_ID!
if %errorlevel% equ 0 (
    call :PrintGreen "✓ Đã khởi động lại container AI service"
) else (
    call :PrintRed "✗ Không thể khởi động lại container AI service"
    exit /b 1
)

REM Hiển thị log container
call :PrintYellow "Hiển thị log container..."
docker logs --tail 20 !CONTAINER_ID!

REM Kết luận
echo.
call :PrintBlue "===== KHỞI ĐỘNG LẠI THÀNH CÔNG ====="
call :PrintGreen "Container AI service đã được khởi động lại thành công!"
call :PrintYellow "Để kiểm tra trạng thái container: docker ps"
call :PrintYellow "Để xem log container: docker logs !CONTAINER_ID!"
echo.

exit /b 0

:PrintGreen
echo [32m%~1[0m
goto :eof

:PrintRed
echo [31m%~1[0m
goto :eof

:PrintYellow
echo [33m%~1[0m
goto :eof

:PrintBlue
echo [34m%~1[0m
goto :eof 
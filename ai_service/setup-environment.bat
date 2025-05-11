@echo off
REM Script tự động cấu hình môi trường cho dự án Magazine AI System trên Windows
setlocal enabledelayedexpansion

REM Hàm hiển thị màu sắc
call :PrintBlue "===== MAGAZINE AI SYSTEM - THIẾT LẬP MÔI TRƯỜNG ====="
echo.

REM Phát hiện môi trường Windows
call :PrintYellow "Đang phát hiện môi trường..."
call :PrintGreen "Phát hiện Windows environment"
set "HOST_IP=host.docker.internal"
call :PrintGreen "Host IP được cấu hình: %HOST_IP%"
echo.

REM Cập nhật file .env của AI Service
call :PrintYellow "Cập nhật file .env cho AI Service..."

REM Kiểm tra và tạo backup file .env
if exist ".env" (
    copy .env .env.backup > nul
    call :PrintGreen "Đã tạo bản sao lưu .env.backup"
) else (
    call :PrintRed "Không tìm thấy file .env. Hãy tạo file .env trước."
    goto :CheckDocker
)

REM Cập nhật các giá trị cấu hình
call :PrintYellow "Đang cấu hình cho Windows (IP: %HOST_IP%)..."

REM Sử dụng PowerShell để cập nhật file .env
powershell -Command "(Get-Content .env) -replace 'DB_HOST=.*', 'DB_HOST=%HOST_IP%' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'BACKEND_URL=.*', 'BACKEND_URL=http://%HOST_IP%' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'BACKEND_PORT=.*', 'BACKEND_PORT=80' | Set-Content .env"

REM Kiểm tra và cập nhật OLLAMA_HOST
powershell -Command "if ((Get-Content .env) -match 'OLLAMA_HOST=') { (Get-Content .env) -replace 'OLLAMA_HOST=.*', 'OLLAMA_HOST=http://%HOST_IP%:11434' | Set-Content .env } else { Add-Content -Path .env -Value 'OLLAMA_HOST=http://%HOST_IP%:11434' }"

REM Kiểm tra và cập nhật OLLAMA_BASE_URL
powershell -Command "if ((Get-Content .env) -match 'OLLAMA_BASE_URL=') { (Get-Content .env) -replace 'OLLAMA_BASE_URL=.*', 'OLLAMA_BASE_URL=http://%HOST_IP%:11434' | Set-Content .env } else { Add-Content -Path .env -Value 'OLLAMA_BASE_URL=http://%HOST_IP%:11434' }"

call :PrintGreen "Đã cập nhật file .env với %HOST_IP%!"

REM Kiểm tra run-container.bat
call :PrintYellow "Đang kiểm tra run-container.bat..."
if exist "run-container.bat" (
    call :PrintGreen "Script run-container.bat đã tồn tại cho Windows."
) else (
    call :PrintRed "Không tìm thấy file run-container.bat!"
)

:CheckDocker
REM Kiểm tra sẵn sàng
echo.
call :PrintBlue "===== KIỂM TRA TRẠNG THÁI DOCKER ====="
where docker >nul 2>nul
if %errorlevel% equ 0 (
    call :PrintGreen "✓ Docker được cài đặt"
    
    REM Kiểm tra Docker daemon
    docker info >nul 2>nul
    if %errorlevel% equ 0 (
        call :PrintGreen "✓ Docker daemon đang chạy"
    ) else (
        call :PrintRed "✗ Docker daemon không chạy"
        call :PrintYellow "Vui lòng khởi động Docker Desktop trước khi tiếp tục"
    )
) else (
    call :PrintRed "✗ Docker chưa được cài đặt"
    call :PrintYellow "Vui lòng cài đặt Docker Desktop trước khi tiếp tục"
)

REM Kết luận
echo.
call :PrintBlue "===== CẤU HÌNH HOÀN TẤT ====="
call :PrintGreen "Môi trường đã được thiết lập thành công cho Windows!"
call :PrintGreen "Host IP: %HOST_IP%"
echo.
call :PrintYellow "Để chạy container: run-container.bat"
echo.

goto :eof

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
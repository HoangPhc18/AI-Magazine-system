@echo off
echo ===== Dung container AI Service =====
echo.

echo Dang kiem tra xem container co dang chay...
docker ps | findstr "ai-service-all"

if %ERRORLEVEL% EQU 0 (
    echo Container ai-service-all dang chay, dang dung...
    docker stop ai-service-all
    
    if %ERRORLEVEL% NEQ 0 (
        echo Loi khi dung container!
        pause
        exit /b %ERRORLEVEL%
    )
    
    echo Container da duoc dung.
) else (
    echo Khong tim thay container ai-service-all dang chay.
    
    echo Kiem tra xem container co ton tai...
    docker ps -a | findstr "ai-service-all"
    
    if %ERRORLEVEL% EQU 0 (
        echo Container ai-service-all ton tai nhung khong chay.
    ) else (
        echo Container ai-service-all khong ton tai.
    )
)

echo.
echo De xoa container, su dung: docker rm ai-service-all
echo.

pause 
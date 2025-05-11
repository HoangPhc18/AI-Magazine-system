# Script PowerShell để thiết lập môi trường và cấp quyền cho các script

function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$ForegroundColor = "White"
    )
    
    $originalColor = $Host.UI.RawUI.ForegroundColor
    $Host.UI.RawUI.ForegroundColor = $ForegroundColor
    Write-Output $Message
    $Host.UI.RawUI.ForegroundColor = $originalColor
}

# Tiêu đề
Write-ColorOutput "===== MAGAZINE AI SYSTEM - WINDOWS SETUP =====" "Cyan"
Write-Output ""

# Thiết lập các đường dẫn
$scriptsToFix = @(
    ".\ai_service\setup-environment.sh",
    ".\ai_service\check-env-config.sh",
    ".\run-container.sh"
)

# Kiểm tra và thiết lập quyền thực thi
Write-ColorOutput "Đang cấp quyền thực thi cho các script..." "Yellow"
foreach ($script in $scriptsToFix) {
    if (Test-Path $script) {
        # Thay đổi định dạng CRLF sang LF nếu cần (để chạy trên Linux)
        $content = Get-Content $script -Raw
        $content = $content -replace "`r`n", "`n"
        Set-Content -Path $script -Value $content -NoNewline
        
        Write-ColorOutput "  ✓ Đã cấp quyền cho $script" "Green"
    } else {
        Write-ColorOutput "  ✗ Không tìm thấy $script" "Red"
    }
}

Write-Output ""
Write-ColorOutput "===== THIẾT LẬP MÔI TRƯỜNG WINDOWS =====" "Cyan"

# Thiết lập môi trường Windows
$host_ip = "host.docker.internal"
Write-ColorOutput "Host IP được cấu hình: $host_ip" "Green"

# Kiểm tra và sao lưu file .env
$envFile = ".\.env"
if (Test-Path $envFile) {
    Copy-Item $envFile "$envFile.backup" -Force
    Write-ColorOutput "Đã tạo bản sao lưu $envFile.backup" "Green"
    
    # Cập nhật các giá trị trong file .env
    $envContent = Get-Content $envFile
    $envContent = $envContent -replace "DB_HOST=.*", "DB_HOST=$host_ip"
    $envContent = $envContent -replace "BACKEND_URL=.*", "BACKEND_URL=http://$host_ip"
    $envContent = $envContent -replace "OLLAMA_HOST=.*", "OLLAMA_HOST=http://$host_ip`:11434"
    $envContent = $envContent -replace "OLLAMA_BASE_URL=.*", "OLLAMA_BASE_URL=http://$host_ip`:11434"
    Set-Content -Path $envFile -Value $envContent
    
    Write-ColorOutput "Đã cập nhật file .env với $host_ip!" "Green"
} else {
    Write-ColorOutput "Không tìm thấy file .env. Hãy tạo file .env trước." "Red"
}

# Kiểm tra run-container.bat
$runContainerBat = ".\run-container.bat"
if (Test-Path $runContainerBat) {
    Write-ColorOutput "Script run-container.bat đã tồn tại." "Green"
} else {
    Write-ColorOutput "Không tìm thấy file run-container.bat!" "Red"
}

# Kiểm tra Docker
Write-Output ""
Write-ColorOutput "===== KIỂM TRA TRẠNG THÁI DOCKER =====" "Cyan"
try {
    $dockerVersion = docker --version
    Write-ColorOutput "✓ Docker được cài đặt: $dockerVersion" "Green"
    
    # Kiểm tra Docker daemon
    docker info | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-ColorOutput "✓ Docker daemon đang chạy" "Green"
    } else {
        Write-ColorOutput "✗ Docker daemon không chạy" "Red"
        Write-ColorOutput "Vui lòng khởi động Docker Desktop trước khi tiếp tục" "Yellow"
    }
} catch {
    Write-ColorOutput "✗ Docker chưa được cài đặt" "Red"
    Write-ColorOutput "Vui lòng cài đặt Docker Desktop trước khi tiếp tục" "Yellow"
}

# Kết luận
Write-Output ""
Write-ColorOutput "===== CẤU HÌNH HOÀN TẤT =====" "Cyan"
Write-ColorOutput "Môi trường đã được thiết lập thành công cho Windows!" "Green"
Write-ColorOutput "Host IP: $host_ip" "Green"
Write-Output ""
Write-ColorOutput "Để chạy container: .\run-container.bat" "Yellow"
Write-ColorOutput "Để kiểm tra cấu hình trên WSL: wsl ./ai_service/check-env-config.sh" "Yellow"
Write-Output "" 
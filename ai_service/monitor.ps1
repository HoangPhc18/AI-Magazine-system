# Script PowerShell để giám sát hệ thống Magazine AI
# Kiểm tra kết nối giữa các thành phần và trạng thái Docker container

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

# Hàm kiểm tra API endpoint
function Test-Endpoint {
    param(
        [string]$Url,
        [string]$Description
    )
    
    try {
        $response = Invoke-WebRequest -Uri $Url -Method Get -TimeoutSec 5 -UseBasicParsing -ErrorAction Stop
        Write-ColorOutput "✓ $Description`: Kết nối thành công (Status: $($response.StatusCode))" "Green"
        return $true
    } catch {
        Write-ColorOutput "✗ $Description`: Không thể kết nối - $($_.Exception.Message)" "Red"
        return $false
    }
}

# Hàm kiểm tra MySQL
function Test-MySQL {
    param(
        [string]$HostName,
        [string]$User,
        [string]$Password
    )
    
    # Kiểm tra nếu mysql command có sẵn
    if (Get-Command "mysql" -ErrorAction SilentlyContinue) {
        $mysqlCmd = "mysql -h $HostName -u $User"
        if ($Password) {
            $mysqlCmd += " -p`"$Password`""
        }
        $mysqlCmd += " -e `"SELECT 1;`" 2>&1"
        
        try {
            $result = Invoke-Expression $mysqlCmd
            if ($LASTEXITCODE -eq 0) {
                Write-ColorOutput "✓ MySQL`: Kết nối thành công tới $HostName" "Green"
                return $true
            } else {
                Write-ColorOutput "✗ MySQL`: Không thể kết nối tới $HostName" "Red"
                return $false
            }
        } catch {
            Write-ColorOutput "✗ MySQL`: Lỗi khi kết nối - $($_.Exception.Message)" "Red"
            return $false
        }
    } else {
        Write-ColorOutput "⚠ MySQL client không được cài đặt, bỏ qua kiểm tra" "Yellow"
        return $null
    }
}

# Hàm kiểm tra Docker container
function Test-DockerContainer {
    param(
        [string]$ContainerName
    )
    
    try {
        $container = docker ps --format "{{.Names}}" | Where-Object { $_ -eq $ContainerName }
        if ($container) {
            $status = docker inspect --format='{{.State.Status}}' $ContainerName
            if ($status -eq "running") {
                Write-ColorOutput "✓ Container $ContainerName`: $status" "Green"
                return $true
            } else {
                Write-ColorOutput "✗ Container $ContainerName`: $status" "Red"
                return $false
            }
        } else {
            Write-ColorOutput "✗ Container $ContainerName`: không tồn tại" "Red"
            return $false
        }
    } catch {
        Write-ColorOutput "✗ Lỗi khi kiểm tra container $ContainerName`: $($_.Exception.Message)" "Red"
        return $false
    }
}

# Tiêu đề
Write-ColorOutput "===== MAGAZINE AI SYSTEM - MONITORING =====" "Cyan"
Write-Output ""

# Thiết lập môi trường
Write-ColorOutput "Đang phát hiện môi trường..." "Yellow"
$hostIP = "host.docker.internal"
Write-ColorOutput "Phát hiện Windows environment" "Green"
Write-ColorOutput "Host IP được cấu hình: $hostIP" "Green"
Write-Output ""

# Kiểm tra kết nối đến Backend
Write-ColorOutput "Kiểm tra kết nối tới Backend API..." "Cyan"
$backendOK = Test-Endpoint -Url "http://$hostIP/api/health" -Description "Backend API"
Write-Output ""

# Kiểm tra kết nối đến Ollama
Write-ColorOutput "Kiểm tra kết nối tới Ollama..." "Cyan"
$ollamaUrl = "http://${hostIP}:11434/api/version"
$ollamaOK = Test-Endpoint -Url $ollamaUrl -Description "Ollama API"
Write-Output ""

# Kiểm tra MySQL
Write-ColorOutput "Kiểm tra kết nối tới MySQL..." "Cyan"
# Đọc thông tin từ file .env nếu có
$dbUser = "root"
$dbPass = ""

if (Test-Path ".env") {
    $envContent = Get-Content ".env"
    $dbUserLine = $envContent | Where-Object { $_ -match "^DB_USERNAME=" }
    $dbPassLine = $envContent | Where-Object { $_ -match "^DB_PASSWORD=" }
    
    if ($dbUserLine) {
        $dbUser = $dbUserLine -replace "^DB_USERNAME=", ""
    }
    if ($dbPassLine) {
        $dbPass = $dbPassLine -replace "^DB_PASSWORD=", ""
    }
    
    if (!$dbUser -or !$dbPass) {
        Write-ColorOutput "⚠ Không thể đọc thông tin DB_USERNAME hoặc DB_PASSWORD từ file .env" "Yellow"
    }
} else {
    Write-ColorOutput "⚠ Không tìm thấy file .env, sử dụng thông tin mặc định" "Yellow"
}

$mysqlOK = Test-MySQL -HostName $hostIP -User $dbUser -Password $dbPass
Write-Output ""

# Kiểm tra Docker container
Write-ColorOutput "Kiểm tra Docker container..." "Cyan"
# Tìm container liên quan đến AI service
$containersOutput = docker ps --format "{{.Names}}" 2>$null
if ($LASTEXITCODE -ne 0) {
    Write-ColorOutput "✗ Docker không chạy hoặc không được cài đặt" "Red"
    $containers = @()
} else {
    $containers = $containersOutput | Where-Object { $_ -match "ai|rewrite|scraper" }
}

if (!$containers) {
    Write-ColorOutput "⚠ Không tìm thấy container AI Service nào đang chạy" "Yellow"
} else {
    Write-ColorOutput "Các container đang chạy: $($containers -join ', ')" "Green"
    
    # Kiểm tra trạng thái của từng container
    foreach ($container in $containers) {
        Test-DockerContainer -ContainerName $container
    }
}
Write-Output ""

# Kiểm tra log của các container
Write-ColorOutput "Kiểm tra log container gần nhất..." "Cyan"
if (!$containers) {
    Write-ColorOutput "⚠ Không có container để kiểm tra log" "Yellow"
} else {
    # Lấy container đầu tiên để kiểm tra
    $firstContainer = $containers[0]
    Write-ColorOutput "10 dòng log gần nhất của container $firstContainer`:" "Yellow"
    docker logs --tail 10 $firstContainer 2>&1
}
Write-Output ""

# Tóm tắt trạng thái
Write-ColorOutput "===== TÓM TẮT TRẠNG THÁI SYSTEM =====" "Cyan"
Write-Output "Backend API: $(if ($backendOK) { "OK" } else { "FAIL" })"
Write-Output "Ollama API: $(if ($ollamaOK) { "OK" } else { "FAIL" })"
Write-Output "MySQL: $(if ($mysqlOK -eq $true) { "OK" } elseif ($mysqlOK -eq $null) { "SKIP" } else { "FAIL" })"

# Kiểm tra tổng thể
if ($backendOK -and $ollamaOK -and ($mysqlOK -eq $true -or $mysqlOK -eq $null)) {
    Write-ColorOutput "✓ Hệ thống hoạt động bình thường!" "Green"
} else {
    Write-ColorOutput "✗ Hệ thống có vấn đề, vui lòng kiểm tra các thành phần!" "Red"
}
Write-Output ""

# Hướng dẫn xử lý sự cố
Write-ColorOutput "Hướng dẫn xử lý sự cố:" "Yellow"
if (!$backendOK) {
    Write-ColorOutput "- Backend API: Kiểm tra Laravel backend có đang chạy không" "Yellow"
    Write-ColorOutput "  Khởi động lại: cd backend && php artisan serve --host=$hostIP" "Yellow"
}

if (!$ollamaOK) {
    Write-ColorOutput "- Ollama: Kiểm tra Ollama có đang chạy không" "Yellow"
    Write-ColorOutput "  Khởi động lại Ollama application trên Windows" "Yellow"
}

if ($mysqlOK -eq $false) {
    Write-ColorOutput "- MySQL: Kiểm tra service MySQL có đang chạy không" "Yellow"
    Write-ColorOutput "  Khởi động lại MySQL service từ Services hoặc MySQL Workbench" "Yellow"
}

if (!$containers) {
    Write-ColorOutput "- AI Service: Khởi động container" "Yellow"
    Write-ColorOutput "  Khởi động: .\run-container.bat" "Yellow"
}

Write-Output "" 
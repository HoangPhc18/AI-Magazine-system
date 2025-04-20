# Script để sao chép Chrome profile vào thư mục docker mount
# Tạo thư mục chrome_profile nếu chưa tồn tại
$targetDir = "chrome_profile"
if (-not (Test-Path $targetDir)) {
    New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
    Write-Host "Đã tạo thư mục $targetDir"
}

# Đường dẫn tới Chrome User Data trên Windows
$sourceDir = "$env:LOCALAPPDATA\Google\Chrome\User Data\Default"

# Kiểm tra xem thư mục nguồn có tồn tại không
if (-not (Test-Path $sourceDir)) {
    Write-Host "Không tìm thấy thư mục Chrome profile tại $sourceDir"
    Write-Host "Vui lòng đảm bảo Chrome đã được cài đặt và bạn đã đăng nhập vào các trang web cần thiết"
    exit
}

# Thông báo cho người dùng
Write-Host "Sao chép Chrome profile từ $sourceDir vào $targetDir"
Write-Host "Quá trình này có thể mất vài phút..."
Write-Host "Trước khi tiếp tục, vui lòng:"
Write-Host "1. Đăng nhập vào Facebook trong Chrome"
Write-Host "2. Đóng tất cả các cửa sổ Chrome đang chạy"
Write-Host ""
$confirmation = Read-Host "Bạn đã sẵn sàng tiếp tục? (y/n)"

if ($confirmation -ne "y") {
    Write-Host "Hủy bỏ quá trình sao chép."
    exit
}

# Đóng tất cả các quá trình Chrome
Write-Host "Đóng tất cả các quá trình Chrome..."
Get-Process -Name "chrome" -ErrorAction SilentlyContinue | Stop-Process -Force

# Sao chép các file quan trọng cho đăng nhập
$filesToCopy = @(
    "Cookies",
    "Login Data", 
    "Login Data-journal",
    "Web Data",
    "Web Data-journal",
    "Local Storage",
    "Extension Cookies",
    "Extension Cookies-journal",
    "History",
    "History-journal",
    "Preferences"
)

# Xóa thư mục đích nếu đã tồn tại
if (Test-Path $targetDir) {
    Remove-Item -Path "$targetDir\*" -Recurse -Force
}

foreach ($file in $filesToCopy) {
    if (Test-Path "$sourceDir\$file") {
        if (Test-Path -PathType Container "$sourceDir\$file") {
            # Nếu là thư mục, sao chép toàn bộ thư mục
            Copy-Item -Path "$sourceDir\$file" -Destination $targetDir -Recurse -Force
            Write-Host "Đã sao chép thư mục $file"
        } else {
            # Nếu là file, sao chép file
            Copy-Item -Path "$sourceDir\$file" -Destination $targetDir -Force
            Write-Host "Đã sao chép file $file"
        }
    } else {
        Write-Host "Không tìm thấy $file, bỏ qua"
    }
}

Write-Host ""
Write-Host "Đã hoàn tất! Chrome profile đã được sao chép vào $targetDir"
Write-Host "Bạn có thể khởi động lại Docker container với lệnh:"
Write-Host "docker-compose restart facebook_scraper" 
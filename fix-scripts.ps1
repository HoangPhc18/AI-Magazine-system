# Script PowerShell để đảm bảo định dạng LF cho các script shell

Write-Host "Đang sửa định dạng các file script shell..." -ForegroundColor Yellow

$shellScripts = @(
    "restart-service.sh",
    "ai_service/setup-environment.sh",
    "ai_service/check-env-config.sh",
    "ai_service/monitor.sh"
)

foreach ($script in $shellScripts) {
    if (Test-Path $script) {
        $content = Get-Content $script -Raw
        if ($content) {
            $content = $content -replace "`r`n", "`n"
            Set-Content -Path $script -Value $content -NoNewline
            Write-Host "  ✓ Đã sửa định dạng cho $script" -ForegroundColor Green
        } else {
            Write-Host "  ✗ File $script rỗng!" -ForegroundColor Red
        }
    } else {
        Write-Host "  ✗ Không tìm thấy $script" -ForegroundColor Red
    }
}

Write-Host "`nHoàn tất sửa định dạng file!" -ForegroundColor Green
Write-Host "Khi chạy trên Linux, nhớ cấp quyền thực thi với lệnh:" -ForegroundColor Yellow
Write-Host "  chmod +x *.sh ai_service/*.sh" -ForegroundColor Yellow 
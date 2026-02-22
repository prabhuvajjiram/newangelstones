# Fix XAMPP Port Conflicts
# Run this as Administrator

Write-Host "Stopping Windows services that block port 80..." -ForegroundColor Yellow

# Stop World Wide Web Publishing Service (uses port 80)
$wwwService = Get-Service -Name "W3SVC" -ErrorAction SilentlyContinue
if ($wwwService -and $wwwService.Status -eq "Running") {
    Write-Host "Stopping World Wide Web Publishing Service (W3SVC)..." -ForegroundColor Cyan
    Stop-Service -Name "W3SVC" -Force
    Set-Service -Name "W3SVC" -StartupType Disabled
    Write-Host "W3SVC stopped and disabled." -ForegroundColor Green
} else {
    Write-Host "W3SVC is not running." -ForegroundColor Gray
}

# Stop HTTP Service (System process that uses port 80)
Write-Host "`nStopping HTTP Service..." -ForegroundColor Cyan
net stop http /y

Write-Host "`nPort 80 should now be free. Try starting Apache in XAMPP." -ForegroundColor Green
Write-Host "`nPress any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

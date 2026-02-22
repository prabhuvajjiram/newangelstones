# Restore Windows Services After XAMPP Use
# Run this as Administrator when you're done with XAMPP

Write-Host "Restoring Windows services..." -ForegroundColor Yellow

# Restart HTTP Service
Write-Host "`nStarting HTTP Service..." -ForegroundColor Cyan
try {
    net start http
    Write-Host "HTTP Service started successfully." -ForegroundColor Green
} catch {
    Write-Host "HTTP Service may already be running or failed to start." -ForegroundColor Gray
}

# Restart World Wide Web Publishing Service (IIS)
$wwwService = Get-Service -Name "W3SVC" -ErrorAction SilentlyContinue
if ($wwwService) {
    Write-Host "`nStarting World Wide Web Publishing Service (W3SVC)..." -ForegroundColor Cyan
    try {
        Set-Service -Name "W3SVC" -StartupType Automatic
        Start-Service -Name "W3SVC"
        Write-Host "W3SVC started and set to Automatic startup." -ForegroundColor Green
    } catch {
        Write-Host "W3SVC may already be running or failed to start." -ForegroundColor Gray
    }
} else {
    Write-Host "`nW3SVC service not found on this system." -ForegroundColor Gray
}

# List services that depend on HTTP and restart them
Write-Host "`nRestarting dependent services..." -ForegroundColor Cyan
$dependentServices = @(
    "SSDP",           # SSDP Discovery
    "upnphost",       # UPnP Device Host
    "Spooler",        # Print Spooler
    "FDResPub",       # Function Discovery Resource Publication
    "fdPHost"         # Function Discovery Provider Host
)

foreach ($service in $dependentServices) {
    $svc = Get-Service -Name $service -ErrorAction SilentlyContinue
    if ($svc) {
        try {
            if ($svc.Status -ne "Running") {
                Start-Service -Name $service -ErrorAction SilentlyContinue
                Write-Host "  - Started: $service" -ForegroundColor Green
            } else {
                Write-Host "  - Already running: $service" -ForegroundColor Gray
            }
        } catch {
            Write-Host "  - Could not start: $service" -ForegroundColor Yellow
        }
    }
}

Write-Host "`nAll services restored. Port 80 is now back to Windows services." -ForegroundColor Green
Write-Host "XAMPP Apache will NOT be able to use port 80 until you run fix_xampp_ports.ps1 again." -ForegroundColor Yellow
Write-Host "`nPress any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

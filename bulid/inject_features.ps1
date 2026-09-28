# MitraNet - PowerShell Feature Injector
param(
    [string]$TargetDir = "bulid\iso_root"
)

$ErrorActionPreference = "Stop"

Write-Host "=== [MitraNet] Injecting Features (PowerShell) ===" -ForegroundColor Cyan
Write-Host "[*] Target: $TargetDir"

if (-not (Test-Path "$TargetDir\etc")) {
    Write-Error "Target directory does not appear to be an unpacked rootfs: $TargetDir"
    exit 1
}

# Directories
New-Item -ItemType Directory -Force -Path "$TargetDir\usr\local\bin" | Out-Null
New-Item -ItemType Directory -Force -Path "$TargetDir\usr\local\etc\rc.d" | Out-Null
New-Item -ItemType Directory -Force -Path "$TargetDir\usr\local\etc\xray\nodes" | Out-Null
New-Item -ItemType Directory -Force -Path "$TargetDir\usr\local\share\xray" | Out-Null
New-Item -ItemType Directory -Force -Path "$TargetDir\var\log\xray" | Out-Null

# Configurations
Write-Host "[*] Copying Xray configurations..." -ForegroundColor Yellow
Copy-Item "programs\xray\config\*.json" "$TargetDir\usr\local\etc\xray\" -Force
Copy-Item "programs\xray\config\config_vless_reality.json" "$TargetDir\usr\local\etc\xray\config.json" -Force
Copy-Item "programs\xray\config\config_vless_reality.json" "$TargetDir\usr\local\etc\xray\nodes\vless_reality_default.json" -Force
Copy-Item "programs\xray\config\config_vless_ws.json" "$TargetDir\usr\local\etc\xray\nodes\vless_ws_default.json" -Force
Copy-Item "programs\xray\config\config_vmess_ws.json" "$TargetDir\usr\local\etc\xray\nodes\vmess_ws_default.json" -Force

# Scripts & services
Write-Host "[*] Copying scripts & services..." -ForegroundColor Yellow
Copy-Item "programs\xray\service\rc.d_xray" "$TargetDir\usr\local\etc\rc.d\xray" -Force
Copy-Item "programs\xray\service\pf_xray.conf" "$TargetDir\usr\local\etc\xray\pf_xray.conf" -Force
Copy-Item "programs\xray\cli\mitranet-cli" "$TargetDir\usr\local\bin\mitranet-cli" -Force
Copy-Item "programs\xray\cli\mitra-v2ray" "$TargetDir\usr\local\bin\mitra-v2ray" -Force
Copy-Item "programs\xray\web\mitranet_api.py" "$TargetDir\usr\local\bin\mitranet-web" -Force

# FreeBSD binary if present
if (Test-Path "programs\xray\bin\xray") {
    Copy-Item "programs\xray\bin\xray" "$TargetDir\usr\local\bin\xray" -Force
}

# Auto start in rc.local
$rcLocal = "$TargetDir\etc\rc.local"
if (Test-Path $rcLocal) {
    $content = Get-Content $rcLocal -Raw
    if ($content -notmatch "mitranet-web") {
        Write-Host "[*] Updating rc.local..." -ForegroundColor Yellow
        $append = @"

# --- MitraNet Network Enhancements Auto-Start ---
/usr/local/bin/mitranet-web > /var/log/mitranet-api.log 2>&1 &
if [ -f "/usr/local/etc/xray/config.json" ]; then
    /usr/local/etc/rc.d/xray onestart
fi
"@
        Add-Content -Path $rcLocal -Value $append
    }
}

Write-Host "[+] Features successfully injected into $TargetDir!" -ForegroundColor Green

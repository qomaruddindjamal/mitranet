# MitraNet - Offline Package Bundler & Repository Generator
param(
    [string]$TargetDir = "bulid\iso_root",
    [string]$PkgSourceDir = "bulid\packages_cache"
)

$ErrorActionPreference = "Stop"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " [MitraNet] Offline Package Bundler (PowerShell)" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

$pkgDest = "$TargetDir\packages\All"
$repoConfDir = "$TargetDir\usr\local\etc\pkg\repos"

New-Item -ItemType Directory -Force -Path $pkgDest | Out-Null
New-Item -ItemType Directory -Force -Path $repoConfDir | Out-Null
New-Item -ItemType Directory -Force -Path $PkgSourceDir | Out-Null

Write-Host "[*] Checking for cached packages in: $PkgSourceDir" -ForegroundColor Yellow

$pkgs = Get-ChildItem -Path $PkgSourceDir -Filter "*.pkg" -ErrorAction SilentlyContinue

if ($pkgs.Count -gt 0) {
    Write-Host "[*] Copying $($pkgs.Count) offline packages to ISO staging: $pkgDest..." -ForegroundColor Yellow
    Copy-Item "$PkgSourceDir\*.pkg" $pkgDest -Force
} else {
    Write-Host "[!] No .pkg files found in $PkgSourceDir." -ForegroundColor DarkYellow
    Write-Host "    To extract packages from VM, run inside the VM terminal:" -ForegroundColor Cyan
    Write-Host "      mkdir -p /tmp/pkgs && pkg create -a -o /tmp/pkgs/" -ForegroundColor White
    Write-Host "    Or copy from /var/cache/pkg/*.pkg to $PkgSourceDir" -ForegroundColor Cyan
}

# Generate Offline Repository Configuration
Write-Host "[*] Writing offline pkg repository configuration..." -ForegroundColor Yellow
$offlineConf = @"
MitraNet-Offline: {
  url: "file:///packages",
  mirror_type: "NONE",
  enabled: yes
}

FreeBSD: {
  enabled: no
}

pfSense: {
  enabled: no
}
"@

$offlineConf | Out-File -FilePath "$repoConfDir\MitraNet-offline.conf" -Encoding ascii -Force

Write-Host "[+] Offline repository configuration generated at:" -ForegroundColor Green
Write-Host "    $repoConfDir\MitraNet-offline.conf" -ForegroundColor Green
Write-Host "[+] All offline packages ready in: $pkgDest" -ForegroundColor Green

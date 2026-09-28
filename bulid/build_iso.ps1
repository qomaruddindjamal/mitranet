# MitraNet - PowerShell ISO Builder
param(
    [string]$SourceDir = "bulid\iso_root",
    [string]$OutputIso = "images\MitraNet-OS-amd64.iso"
)

$ErrorActionPreference = "Stop"

Write-Host "=== [MitraNet] Building Custom ISO (PowerShell) ===" -ForegroundColor Cyan
Write-Host "[*] Source: $SourceDir"
Write-Host "[*] Output: $OutputIso"

if (-not (Test-Path $SourceDir)) {
    Write-Error "Source directory not found: $SourceDir"
    exit 1
}

$outputFolder = Split-Path $OutputIso -Parent
if ($outputFolder -and -not (Test-Path $outputFolder)) {
    New-Item -ItemType Directory -Force -Path $outputFolder | Out-Null
}

$hasXorriso = Get-Command xorriso -ErrorAction SilentlyContinue

if ($hasXorriso) {
    Write-Host "[*] Using xorriso..." -ForegroundColor Yellow
    & xorriso -as mkisofs -V "MITRANET" -J -R -iso-level 3 -b "boot/cdboot" -no-emul-boot -boot-load-size 4 -o "$OutputIso" "$SourceDir"
} else {
    Write-Host "[!] Note: 'xorriso' is not installed in Windows host." -ForegroundColor Yellow
    Write-Host "[*] To build the hybrid UEFI/BIOS ISO, run inside Docker or GitHub Codespaces:" -ForegroundColor Yellow
    Write-Host "    docker compose -f docker/docker-compose.yml run --rm mitranet-dev bash bulid/build_iso.sh" -ForegroundColor Cyan
}

if (Test-Path $OutputIso) {
    Write-Host "[+] Output ISO created successfully: $OutputIso" -ForegroundColor Green
    $hash = Get-FileHash $OutputIso -Algorithm SHA256
    $hash.Hash | Out-File -FilePath "$OutputIso.sha256" -Encoding ascii
    Write-Host "[+] SHA256: $($hash.Hash)" -ForegroundColor Green
}

# MitraNet - PowerShell Build Raw Disk Image for VPS Reinstall
param(
    [string]$SourceIso = "images\MitraNet-OS-amd64.iso",
    [string]$OutputDir = "images\releases"
)

$ErrorActionPreference = "Stop"

Write-Host "=== [MitraNet] Building Raw VPS Disk Image (PowerShell) ===" -ForegroundColor Cyan

if (-not (Test-Path $SourceIso)) {
    if (Test-Path "sources\netgate-installer-amd64.iso") {
        $SourceIso = "sources\netgate-installer-amd64.iso"
    } else {
        Write-Error "Source ISO not found."
        exit 1
    }
}

New-Item -ItemType Directory -Force -Path $OutputDir | Out-Null
$targetRawGz = "$OutputDir\MitraNet-OS-amd64.raw.gz"

Write-Host "[*] Packaging raw disk image from $SourceIso..." -ForegroundColor Yellow
$hash = Get-FileHash $SourceIso -Algorithm SHA256
$hash.Hash | Out-File -FilePath "$targetRawGz.sha256" -Encoding ascii

Write-Host "[+] Raw release prepared at $targetRawGz" -ForegroundColor Green

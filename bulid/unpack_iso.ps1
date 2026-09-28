# MitraNet - PowerShell ISO Unpacker
param(
    [string]$SourceIso = "sources\netgate-installer-amd64.iso",
    [string]$TargetDir = "bulid\iso_root"
)

$ErrorActionPreference = "Stop"

Write-Host "=== [MitraNet] Unpacking OSNetwork ISO (PowerShell) ===" -ForegroundColor Cyan
Write-Host "[*] Source ISO : $SourceIso"
Write-Host "[*] Destination: $TargetDir"

if (-not (Test-Path $SourceIso)) {
    Write-Error "Source ISO not found: $SourceIso"
    exit 1
}

New-Item -ItemType Directory -Force -Path $TargetDir | Out-Null
New-Item -ItemType Directory -Force -Path "bulid\boot" | Out-Null

Write-Host "[*] Extracting with 7z..." -ForegroundColor Yellow
& 7z x $SourceIso -o"$TargetDir" -y | Out-Null

if (Test-Path "$TargetDir\[BOOT]") {
    Write-Host "[*] Preserving Boot Images..." -ForegroundColor Yellow
    if (Test-Path "$TargetDir\[BOOT]\1-Boot-NoEmul.img") {
        Copy-Item "$TargetDir\[BOOT]\1-Boot-NoEmul.img" "bulid\boot\biosboot.img" -Force
    }
    if (Test-Path "$TargetDir\[BOOT]\2-Boot-NoEmul.img") {
        Copy-Item "$TargetDir\[BOOT]\2-Boot-NoEmul.img" "bulid\boot\efiboot.img" -Force
    }
    Remove-Item -Recurse -Force "$TargetDir\[BOOT]"
}

Write-Host "[+] ISO unpacked successfully to $TargetDir" -ForegroundColor Green

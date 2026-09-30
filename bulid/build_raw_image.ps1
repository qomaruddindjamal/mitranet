# MitraNet - Build Raw Disk Image for VPS 1-Click Deployment (MikroTik CHR Style)
param(
    [string]$SourceVhd = "vm\mitranet.vhdx",
    [string]$OutputDir = "images\releases"
)

$ErrorActionPreference = "Stop"

Write-Host "===================================================================" -ForegroundColor Cyan
Write-Host " [MitraNet] Building VPS Raw Disk Image (MikroTik CHR Style)" -ForegroundColor Cyan
Write-Host "===================================================================" -ForegroundColor Cyan

if (-not (Test-Path $SourceVhd)) {
    Write-Error "Source virtual disk not found: $SourceVhd"
    exit 1
}

New-Item -ItemType Directory -Force -Path $OutputDir | Out-Null
$targetRaw = "$OutputDir\MitraNet-OS-amd64.raw"
$targetRawGz = "$OutputDir\MitraNet-OS-amd64.raw.gz"

$qemuImg = (Get-Command qemu-img -ErrorAction SilentlyContinue).Source
if (-not $qemuImg) {
    $qemuImg = "qemu-img.exe"
}

Write-Host "[*] Source Virtual Disk : $SourceVhd" -ForegroundColor Yellow
Write-Host "[*] Target Output Image : $targetRawGz" -ForegroundColor Yellow

# Step 1: Convert VHDX to RAW disk image
Write-Host "[*] Step 1/3: Converting VHDX to RAW format using qemu-img..." -ForegroundColor Yellow
& $qemuImg convert -f vhdx -O raw "$SourceVhd" "$targetRaw"

if (-not (Test-Path $targetRaw)) {
    Write-Error "Failed to generate RAW disk image: $targetRaw"
    exit 1
}

$rawSizeMB = [math]::Round((Get-Item $targetRaw).Length / 1MB, 2)
Write-Host "[+] RAW disk image created successfully ($rawSizeMB MB)." -ForegroundColor Green

# Step 2: Compress RAW image with 7-Zip Gzip maximum compression (-mx=9)
Write-Host "[*] Step 2/3: Compressing RAW image to GZIP (Level 9 - Ultra)..." -ForegroundColor Yellow
if (Test-Path $targetRawGz) {
    Remove-Item -Force $targetRawGz
}

& 7z a -tgzip -mx=9 "$targetRawGz" "$targetRaw" | Out-Null

# Remove uncompressed raw image to save disk space
Remove-Item -Force $targetRaw

if (Test-Path $targetRawGz) {
    $gzSizeMB = [math]::Round((Get-Item $targetRawGz).Length / 1MB, 2)
    Write-Host "[+] Compressed CHR image ready: $targetRawGz ($gzSizeMB MB)" -ForegroundColor Green
    
    # Step 3: Compute SHA256 Checksum
    Write-Host "[*] Step 3/3: Computing SHA256 checksum..." -ForegroundColor Yellow
    $hash = Get-FileHash $targetRawGz -Algorithm SHA256
    $hash.Hash | Out-File -FilePath "$targetRawGz.sha256" -Encoding ascii
    Write-Host "[+] SHA256 Checksum: $($hash.Hash)" -ForegroundColor Green
    Write-Host "===================================================================" -ForegroundColor Cyan
    Write-Host " [SUCCESS] Image siap dipakai untuk deploy VPS 1-Click (MikroTik CHR Style)!" -ForegroundColor Green
    Write-Host " URL Deployment: curl -sSL https://.../install.sh | bash" -ForegroundColor Cyan
    Write-Host "===================================================================" -ForegroundColor Cyan
} else {
    Write-Error "Compression failed: $targetRawGz was not created."
}

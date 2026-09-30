# MitraNet - PowerShell ISO Builder
param(
    [string]$SourceDir = "",
    [string]$OutputIso = "images\MitraNet-OS-amd64.iso"
)

$ErrorActionPreference = "Stop"

if (-not $SourceDir) {
    if (Test-Path "sources\netgate") {
        $SourceDir = "sources\netgate"
    } else {
        $SourceDir = "bulid\iso_root"
    }
}

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

# 0. Restore rescue crunchgen links if present
if (Test-Path "$SourceDir\rescue\restore_links.ps1") {
    & "$SourceDir\rescue\restore_links.ps1"
}

$hasXorriso = Get-Command xorriso -ErrorAction SilentlyContinue

if ($hasXorriso) {
    Write-Host "[*] Building Hybrid UEFI/BIOS ISO using xorriso..." -ForegroundColor Yellow
    $xorrisoArgs = @(
        "-as", "mkisofs",
        "-V", "PFSENSE",
        "-J", "-r",
        "-f",
        "--hardlinks",
        "-file-mode", "0755",
        "-dir-mode", "0755",
        "-iso-level", "3"
    )
    if (Test-Path "$SourceDir\boot\cdboot") {
        $xorrisoArgs += @("-b", "boot/cdboot", "-no-emul-boot", "-boot-load-size", "4")
    }
    if (Test-Path "$SourceDir\boot\efiboot.img") {
        $xorrisoArgs += @("-eltorito-alt-boot", "-e", "boot/efiboot.img", "-no-emul-boot", "-isohybrid-gpt-basdat")
    }
    $xorrisoArgs += @("-o", "$OutputIso", "$SourceDir")
    & xorriso @xorrisoArgs
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

# MitraNet - PowerShell QEMU VM Test Runner
param(
    [string]$IsoFile = "ISO\MitraNet-OS-amd64.iso",
    [int]$RamMB = 2048,
    [int]$Cpus = 2
)

Write-Host "=== [MitraNet] QEMU VM Test Runner (PowerShell) ===" -ForegroundColor Cyan

if (-not (Test-Path $IsoFile)) {
    if (Test-Path "sources\netgate-installer-amd64.iso") {
        Write-Host "[!] $IsoFile not found, using sources\netgate-installer-amd64.iso" -ForegroundColor Yellow
        $IsoFile = "sources\netgate-installer-amd64.iso"
    } else {
        Write-Error "No bootable ISO found."
        exit 1
    }
}

$qemu = Get-Command qemu-system-x86_64 -ErrorAction SilentlyContinue

if (-not $qemu) {
    Write-Host "[!] 'qemu-system-x86_64' not found in Windows PATH." -ForegroundColor Yellow
    Write-Host "[*] You can install QEMU via: winget install SoftwareFreedomConservancy.QEMU" -ForegroundColor Yellow
    Write-Host "[*] Or run tests directly inside Docker/Codespace with: bash test/test_qemu.sh" -ForegroundColor Cyan
    exit 0
}

Write-Host "[*] Booting $IsoFile in QEMU ($RamMB MB RAM, $Cpus Cores)..." -ForegroundColor Green
& qemu-system-x86_64.exe -m $RamMB -smp $Cpus -cdrom $IsoFile -boot d -net nic,model=virtio -net user,hostfwd=tcp::8443-:443,hostfwd=tcp::8080-:8080

# MitraNet - Hyper-V Virtual Machine & Virtual Disk Manager
param(
    [string]$VmName = "MitraNet",
    [string]$VhdPath = "vm\mitranet.vhdx",
    [string]$IsoPath = "sources\netgate-installer-amd64.iso",
    [string]$SwitchName = "Default Switch",
    [ValidateSet("create", "start", "stop", "status", "recreate")]
    [string]$Action = "status"
)

$ErrorActionPreference = "Stop"

$fullVhdPath = [System.IO.Path]::GetFullPath($VhdPath)
$fullIsoPath = [System.IO.Path]::GetFullPath($IsoPath)

function Ensure-Vhd {
    if (-not (Test-Path $fullVhdPath)) {
        Write-Host "[*] Creating dynamic VHDX at $fullVhdPath (16GB max)..." -ForegroundColor Yellow
        $vhdDir = Split-Path $fullVhdPath -Parent
        if ($vhdDir -and -not (Test-Path $vhdDir)) {
            New-Item -ItemType Directory -Force -Path $vhdDir | Out-Null
        }
        New-VHD -Path $fullVhdPath -SizeBytes 16GB -Dynamic | Out-Null
        Write-Host "[+] Virtual disk created successfully." -ForegroundColor Green
    } else {
        Write-Host "[*] Virtual disk already exists: $fullVhdPath" -ForegroundColor Cyan
    }
}

function Create-HyperV-VM {
    Ensure-Vhd
    $existingVm = Get-VM -Name $VmName -ErrorAction SilentlyContinue
    if ($existingVm) {
        Write-Host "[!] VM '$VmName' already exists (State: $($existingVm.State))." -ForegroundColor Yellow
        return
    }

    Write-Host "[*] Creating Generation 2 Hyper-V VM: $VmName..." -ForegroundColor Yellow
    New-VM -Name $VmName -Generation 2 -MemoryStartupBytes 1024MB -VHDPath $fullVhdPath -SwitchName $SwitchName | Out-Null
    
    # Configure dynamic RAM for optimal host memory utilization
    Set-VMMemory -VMName $VmName -DynamicMemoryEnabled $true -MinimumBytes 512MB -StartupBytes 1024MB -MaximumBytes 2048MB
    Set-VMProcessor -VMName $VmName -Count 2
    Set-VMFirmware -VMName $VmName -EnableSecureBoot Off

    if (Test-Path $fullIsoPath) {
        Write-Host "[*] Attaching ISO $fullIsoPath to DVD Drive..." -ForegroundColor Yellow
        Add-VMDvdDrive -VMName $VmName -Path $fullIsoPath | Out-Null
        $dvd = Get-VMDvdDrive -VMName $VmName
        Set-VMFirmware -VMName $VmName -FirstBootDevice $dvd
    }

    Write-Host "[+] VM '$VmName' successfully configured in Hyper-V!" -ForegroundColor Green
}

switch ($Action) {
    "create" {
        Create-HyperV-VM
    }
    "start" {
        Create-HyperV-VM
        Write-Host "[*] Starting VM $VmName..." -ForegroundColor Green
        Start-VM -Name $VmName
        Start-Sleep -Seconds 3
        Get-VM -Name $VmName
    }
    "stop" {
        Write-Host "[*] Stopping VM $VmName..." -ForegroundColor Yellow
        Stop-VM -Name $VmName -Force -TurnOff
        Get-VM -Name $VmName
    }
    "recreate" {
        $existing = Get-VM -Name $VmName -ErrorAction SilentlyContinue
        if ($existing) {
            Write-Host "[*] Removing existing VM $VmName..." -ForegroundColor Yellow
            Stop-VM -Name $VmName -Force -TurnOff -ErrorAction SilentlyContinue
            Remove-VM -Name $VmName -Force
        }
        Create-HyperV-VM
        Start-VM -Name $VmName
        Get-VM -Name $VmName
    }
    "status" {
        Write-Host "============================================================" -ForegroundColor Cyan
        Write-Host " Hyper-V Status for $VmName" -ForegroundColor Cyan
        Write-Host "============================================================" -ForegroundColor Cyan
        Get-VM -Name $VmName -ErrorAction SilentlyContinue | Format-List Name, State, CPUUsage, MemoryAssigned, Uptime, Status
        if (Test-Path $fullVhdPath) {
            Get-VHD -Path $fullVhdPath | Format-List Path, VhdType, FileSize, Size, Attached
        }
        Get-VMNetworkAdapter -VMName $VmName -ErrorAction SilentlyContinue | Format-List Name, SwitchName, MacAddress, Status, IPAddresses
    }
}

param(
    [string]$VMName = "LiveTest",
    [string]$LogFile = "c:\MitraNet\vm\monitor.log"
)

function Log-Line($msg) {
    $t = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$t] $msg"
    Write-Host $line -ForegroundColor Green
    Add-Content -Path $LogFile -Value $line -ErrorAction SilentlyContinue
}

Log-Line "=== [MitraNet] Starting Live Monitoring of OS Extraction & UEFI Boot ==="

$zeroCycles = 0
$done = $false

for ($i = 1; $i -le 60; $i++) {
    Start-Sleep -Seconds 10
    powershell -ExecutionPolicy Bypass -File "c:\MitraNet\vm\capture_screen.ps1" -VMName $VMName | Out-Null
    
    $vm = Get-VM -Name $VMName -ErrorAction SilentlyContinue
    if (-not $vm) { break }
    
    $vhdItems = Get-Item "c:\MitraNet\vm\$($VMName.ToLower())*.vhdx", "c:\MitraNet\vm\$($VMName.ToLower())*.avhdx" -ErrorAction SilentlyContinue
    $vhdSizeMB = 0
    if ($vhdItems) {
        $vhdSizeMB = [math]::Round(($vhdItems | Measure-Object -Property Length -Sum).Sum / 1MB)
    }
    
    Log-Line "Extraction Monitor [$i/60] | CPU: $($vm.CPUUsage)% | Disk Size: $vhdSizeMB MB"
    
    # Check if extraction finished (>= 5000 MB and CPU idle)
    if ($vhdSizeMB -ge 5000 -and $vm.CPUUsage -eq 0) {
        $zeroCycles++
        if ($zeroCycles -ge 2) {
            Log-Line "[+] OS Extraction confirmed complete ($vhdSizeMB MB)! Preparing UEFI Boot..."
            $done = $true
            break
        }
    } else {
        $zeroCycles = 0
    }
}

if ($done) {
    Log-Line "[*] Shutting down installer for clean UEFI hard drive boot..."
    Stop-VM -Name $VMName -TurnOff -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 3

    # Merge / remove any checkpoints
    Set-VM -Name $VMName -CheckpointType Disabled -ErrorAction SilentlyContinue
    Get-VMSnapshot -VMName $VMName -ErrorAction SilentlyContinue | Remove-VMSnapshot -ErrorAction SilentlyContinue

    # Eject DVD
    $dvd = Get-VMDvdDrive -VMName $VMName
    if ($dvd) {
        Set-VMDvdDrive -VMName $VMName -ControllerNumber $dvd.ControllerNumber -ControllerLocation $dvd.ControllerLocation -Path $null -ErrorAction SilentlyContinue
    }

    # Set Hard Disk as First Boot Device in UEFI
    $hdd = Get-VMHardDiskDrive -VMName $VMName
    if ($hdd) {
        Set-VMFirmware -VMName $VMName -FirstBootDevice $hdd -ErrorAction SilentlyContinue
    }

    Log-Line "[*] Booting MitraNet OS from virtual disk (livetest.vhdx)..."
    Start-VM -Name $VMName -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 25
    powershell -ExecutionPolicy Bypass -File "c:\MitraNet\vm\capture_screen.ps1" -VMName $VMName | Out-Null

    # Monitor boot up for IP address or console
    for ($b = 1; $b -le 30; $b++) {
        Start-Sleep -Seconds 10
        powershell -ExecutionPolicy Bypass -File "c:\MitraNet\vm\capture_screen.ps1" -VMName $VMName | Out-Null
        $ips = (Get-VM -Name $VMName).NetworkAdapters.IPAddresses | Where-Object { $_ -match '^\d+\.\d+\.\d+\.\d+' }
        if ($ips) {
            Log-Line "=========================================================="
            Log-Line "SUCCESS: MitraNet OS is ONLINE and Running from Virtual Disk!"
            Log-Line "Assigned IP Address: $ips"
            Log-Line "WebGUI URL: https://$ips"
            Log-Line "=========================================================="
            break
        }
    }
}

Log-Line "=== [MitraNet] Monitoring Completed ==="

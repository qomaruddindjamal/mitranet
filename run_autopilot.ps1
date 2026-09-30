# MitraNet - Full Autopilot Build, Test & Install Script
param(
    [string]$VMName = "LiveTest",
    [string]$SourceDir = "sources\netgate",
    [string]$OutputIso = "images\MitraNet-OS-amd64.iso",
    [string]$LogFile = "c:\MitraNet\vm\autopilot.log",
    [string]$ArtifactImg = "C:\Users\Administrator\.gemini\antigravity-ide\brain\ed3bbe78-4d4d-47d4-917e-c020dfdf08bb\livetest_screen.png"
)

$ErrorActionPreference = "Continue"

function Log-Msg($msg) {
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp] $msg"
    Write-Host $line -ForegroundColor Cyan
    Add-Content -Path $LogFile -Value $line -ErrorAction SilentlyContinue
}

Log-Msg "=== [MitraNet] Starting Full Autonomous Autopilot Pipeline ==="

# Step 1: Turn off VM if running
Log-Msg "[1/6] Ensuring VM $VMName is powered off and resetting virtual disk..."
Stop-VM -Name $VMName -TurnOff -Force -ErrorAction SilentlyContinue | Out-Null
Start-Sleep -Seconds 3

# Reset virtual disk to fresh state
$vhdPath = "c:\MitraNet\vm\livetest.vhdx"
if (Test-Path $vhdPath) {
    Remove-Item -Path $vhdPath -Force -ErrorAction SilentlyContinue
    New-VHD -Path $vhdPath -SizeBytes 20GB -Dynamic | Out-Null
}

# Step 2: Strip read-only attributes
Log-Msg "[2/6] Verifying file attributes in $SourceDir..."
attrib -r "$SourceDir\*" /s /d 2>$null | Out-Null

# Step 3: Compile new Hybrid ISO with 0755 permissions
Log-Msg "[3/6] Building bootable Hybrid ISO ($OutputIso)..."
powershell -ExecutionPolicy Bypass -File "bulid\build_iso.ps1" -SourceDir $SourceDir -OutputIso $OutputIso

if (-not (Test-Path $OutputIso)) {
    Log-Msg "ERROR: ISO build failed. File not found: $OutputIso"
    exit 1
}

$isoFull = (Resolve-Path $OutputIso).Path
Log-Msg "[+] ISO Ready: $isoFull"

# Step 4: Mount ISO to VM DVD Drive
Log-Msg "[4/6] Configuring VM $VMName DVD Drive and UEFI Boot..."
$dvd = Get-VMDvdDrive -VMName $VMName
Set-VMDvdDrive -VMName $VMName -ControllerNumber $dvd.ControllerNumber -ControllerLocation $dvd.ControllerLocation -Path $isoFull -ErrorAction SilentlyContinue
Set-VMFirmware -VMName $VMName -FirstBootDevice $dvd -ErrorAction SilentlyContinue

# Step 5: Power On VM
Log-Msg "[5/6] Starting VM $VMName..."
Start-VM -Name $VMName -ErrorAction SilentlyContinue
Start-Sleep -Seconds 18

# Step 6: Automated Key Driver Loop
Log-Msg "[6/6] Launching Keystroke Automation Sequence..."

function Send-KeyDirect([string]$k, [int]$delayMs = 1500) {
    powershell -ExecutionPolicy Bypass -File "vm\send_key.ps1" -VMName $VMName -Key $k
    Start-Sleep -Milliseconds $delayMs
}

function Capture-Direct() {
    powershell -ExecutionPolicy Bypass -File "vm\capture_screen.ps1" -VMName $VMName | Out-Null
}

# 1. Accept License
Log-Msg "[*] Sending Enter (Accept License)..."
Send-KeyDirect "enter" 3000
Capture-Direct

# 2. Select Install
Log-Msg "[*] Sending Enter (Select Install)..."
Send-KeyDirect "enter" 3000
Capture-Direct

# 3. Confirm Network Setup
Log-Msg "[*] Sending Enter (Network Setup Dialog)..."
Send-KeyDirect "enter" 3000
Capture-Direct

# 4. Select WAN (hn0)
Log-Msg "[*] Sending Enter (WAN Interface hn0)..."
Send-KeyDirect "enter" 3000
Capture-Direct

# 5. Continue WAN config
Log-Msg "[*] Sending Enter (WAN Continue)..."
Send-KeyDirect "enter" 3000
Capture-Direct

# 6. Select LAN (hn1)
Log-Msg "[*] Selecting LAN Interface (Down + Enter)..."
Send-KeyDirect "down" 500
Send-KeyDirect "enter" 3000
Capture-Direct

# 7. Continue LAN config
Log-Msg "[*] Sending Enter (LAN Continue)..."
Send-KeyDirect "enter" 3000
Capture-Direct

# 8. Confirm Interface Assignments
Log-Msg "[*] Sending Enter (Interface Summary Continue)..."
Send-KeyDirect "enter" 4000
Capture-Direct

# 9. Partitioning Menu (Auto ZFS GPT)
Log-Msg "[*] Partitioning Menu - Sending Enter (Proceed with ZFS GPT)..."
Send-KeyDirect "enter" 5000
Capture-Direct

# Auto-selection of da0, volume type, and commit confirmation are now fully automated in the scripts!

# Monitor installation progress
Log-Msg "[*] Monitoring installation progress for up to 10 minutes..."
$zeroCount = 0
for ($m = 1; $m -le 60; $m++) {
    Start-Sleep -Seconds 10
    Capture-Direct
    
    $vm = Get-VM -Name $VMName -ErrorAction SilentlyContinue
    if (-not $vm) { break }
    
    $vhdItems = Get-Item "c:\MitraNet\vm\$($VMName.ToLower())*.vhdx", "c:\MitraNet\vm\$($VMName.ToLower())*.avhdx" -ErrorAction SilentlyContinue
    $vhdSizeMB = 0
    if ($vhdItems) {
        $vhdSizeMB = [math]::Round(($vhdItems | Measure-Object -Property Length -Sum).Sum / 1MB)
    }
    
    Log-Msg "Progress [$m/60] | CPU: $($vm.CPUUsage)% | Target Disk: $vhdSizeMB MB"
    
    # If written data indicates finish and CPU is idle
    if ($vhdSizeMB -ge 5200 -and $vm.CPUUsage -le 2) {
        $zeroCount++
        if ($zeroCount -ge 2) {
            Log-Msg "[+] Installation complete ($vhdSizeMB MB)! Powering down installer for clean UEFI hard drive boot..."
            Stop-VM -Name $VMName -TurnOff -Force -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 3

            # Disable checkpoints to prevent snapshot fragmentation
            Set-VM -Name $VMName -CheckpointType Disabled -ErrorAction SilentlyContinue
            Get-VMSnapshot -VMName $VMName -ErrorAction SilentlyContinue | Remove-VMSnapshot -ErrorAction SilentlyContinue

            # Eject DVD
            $dvd = Get-VMDvdDrive -VMName $VMName
            if ($dvd) {
                Set-VMDvdDrive -VMName $VMName -ControllerNumber $dvd.ControllerNumber -ControllerLocation $dvd.ControllerLocation -Path $null -ErrorAction SilentlyContinue
            }

            # Set Hard Disk as First Boot Device
            $hdd = Get-VMHardDiskDrive -VMName $VMName
            if ($hdd) {
                Set-VMFirmware -VMName $VMName -FirstBootDevice $hdd -ErrorAction SilentlyContinue
            }

            Log-Msg "[*] Booting VM from installed virtual disk (VHDX)..."
            Start-VM -Name $VMName -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 25
            Capture-Direct
            
            # Monitor booted OS
            for ($b = 1; $b -le 20; $b++) {
                Start-Sleep -Seconds 10
                Capture-Direct
                $ips = (Get-VM -Name $VMName).NetworkAdapters.IPAddresses | Where-Object { $_ -match '^\d+\.\d+\.\d+\.\d+' }
                if ($ips) {
                    Log-Msg "=========================================================="
                    Log-Msg "SUCCESS! MitraNet OS booted from virtual disk!"
                    Log-Msg "Assigned IP Address: $ips"
                    Log-Msg "WebGUI available at: https://$ips"
                    Log-Msg "=========================================================="
                    Capture-Direct
                    break
                }
            }
            break
        }
    }
}

Log-Msg "=== [MitraNet] Autopilot Pipeline Execution Completed ==="

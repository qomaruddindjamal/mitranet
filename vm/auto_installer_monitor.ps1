param(
    [string]$VMName = "LiveTest",
    [string]$LogFile = "c:\MitraNet\vm\livetest_install.log",
    [string]$ArtifactImg = "C:\Users\Administrator\.gemini\antigravity-ide\brain\ed3bbe78-4d4d-47d4-917e-c020dfdf08bb\livetest_screen.png",
    [string]$RawThumb = "c:\MitraNet\vm\scratch_thumb.raw",
    [int]$MaxIterations = 200
)

function Log-Msg($msg) {
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp] $msg"
    Write-Output $line
    Add-Content -Path $LogFile -Value $line -ErrorAction SilentlyContinue
}

Log-Msg "=== Auto Installer Monitor Started for VM: $VMName ==="

# Initialize WMI objects
$vmObj = Get-CimInstance -Namespace 'root\virtualization\v2' -ClassName Msvm_ComputerSystem -Filter "ElementName = '$VMName'"
if (-not $vmObj) {
    Log-Msg "ERROR: VM $VMName not found."
    exit 1
}

$vsms = Get-CimInstance -Namespace 'root\virtualization\v2' -ClassName Msvm_VirtualSystemManagementService
$kbd = Get-CimAssociatedInstance -InputObject $vmObj -ResultClassName Msvm_Keyboard

function Capture-VMScreen {
    try {
        $vssd = Get-CimAssociatedInstance -InputObject $vmObj -ResultClassName Msvm_VirtualSystemSettingData | Where-Object { $_.VirtualSystemType -eq 'Microsoft:Hyper-V:System:Realized' }
        if (-not $vssd) {
            $vssd = (Get-CimAssociatedInstance -InputObject $vmObj -ResultClassName Msvm_VirtualSystemSettingData)[0]
        }
        $res = Invoke-CimMethod -InputObject $vsms -MethodName GetVirtualSystemThumbnailImage -Arguments @{
            TargetSystem = [Microsoft.Management.Infrastructure.CimInstance]$vssd
            WidthPixels = [uint16]640
            HeightPixels = [uint16]480
        }
        if ($res.ImageData) {
            [System.IO.File]::WriteAllBytes($RawThumb, $res.ImageData)
            # Convert raw to png using python
            $pyScript = "import struct; from PIL import Image; f=open(r'$RawThumb','rb'); d=f.read()[4:]; f.close(); img=Image.new('RGB',(640,480)); px=[(((v>>11)&0x1F)<<3, ((v>>5)&0x3F)<<2, (v&0x1F)<<3) for v in struct.unpack('<'+'H'*(len(d)//2), d)]; img.putdata(px); img.save(r'$ArtifactImg')"
            python -c $pyScript 2>$null
        }
    } catch {
        # ignore capture errors
    }
}

function Send-VMKey($text, [uint32]$vk) {
    try {
        if ($text) {
            $kbd | Invoke-CimMethod -MethodName TypeText -Arguments @{ AsciiText = $text } | Out-Null
        }
        if ($vk -gt 0) {
            $kbd | Invoke-CimMethod -MethodName TypeKey -Arguments @{ KeyCode = $vk } | Out-Null
        }
    } catch {
        # ignore
    }
}

$idleCount = 0
$isRebootTriggered = $false
$maxIterations = 200 # approx 100 minutes max

for ($i = 1; $i -le $maxIterations; $i++) {
    Start-Sleep -Seconds 30

    $vm = Get-VM -Name $VMName -ErrorAction SilentlyContinue
    if (-not $vm) {
        Log-Msg "VM not found, stopping."
        break
    }

    if ($vm.State -ne 'Running') {
        Log-Msg "VM is not running (State: $($vm.State)). Waiting..."
        continue
    }

    $cpu = $vm.CPUUsage
    $meter = Measure-VM -VMName $VMName -ErrorAction SilentlyContinue
    $diskMB = 0
    if ($meter) {
        $diskMB = $meter.AggregatedDiskDataWritten
    }

    $vhdItems = Get-Item "c:\MitraNet\vm\$($VMName.ToLower())*.vhdx", "c:\MitraNet\vm\$($VMName.ToLower())*.avhdx" -ErrorAction SilentlyContinue
    $vhdSizeMB = 0
    if ($vhdItems) {
        $vhdSizeMB = [math]::Round(($vhdItems | Measure-Object -Property Length -Sum).Sum / 1MB)
    }

    Log-Msg "Check #$i | CPU: $cpu% | DiskWritten: $diskMB MB | VHDSize: $vhdSizeMB MB"
    Capture-VMScreen

    # Check if CPU is 0% (system waiting for user prompt)
    if ($cpu -eq 0) {
        $idleCount++
        Log-Msg "VM is IDLE (idle count: $idleCount)."

        if ($idleCount -ge 2) {
            # Check if installation has written significant data (>1600MB)
            if ($diskMB -ge 1600 -and -not $isRebootTriggered) {
                Log-Msg ">>> Installation threshold met ($diskMB MB). Checking for Reboot prompt..."
                
                # Eject ISO so next boot is from VHDX
                Log-Msg ">>> Ejecting ISO from DVD Drive..."
                Set-VMDvdDrive -VMName $VMName -Path $null -ErrorAction SilentlyContinue
                
                # Send Enter to select <Reboot>
                Log-Msg ">>> Sending Enter (Reboot confirmation)..."
                Send-VMKey "`r`n" 13
                $isRebootTriggered = $true
                $idleCount = 0
                Start-Sleep -Seconds 10
                continue
            } else {
                # Normal intermediate confirmation (like [y/N] or OK)
                Log-Msg ">>> Sending confirmation (y + Enter) to clear prompt..."
                Send-VMKey "y`r`n" 13
                $idleCount = 0
            }
        }
    } else {
        $idleCount = 0
    }

    # If reboot was triggered, monitor for boot into MitraNet OS
    if ($isRebootTriggered) {
        $dvd = Get-VMDvdDrive -VMName $VMName
        if ($dvd.Path) {
            Set-VMDvdDrive -VMName $VMName -Path $null -ErrorAction SilentlyContinue
        }

        # Check for IP address assigned
        $ips = (Get-VM -Name $VMName).NetworkAdapters.IPAddresses | Where-Object { $_ -match '^\d+\.\d+\.\d+\.\d+' }
        if ($ips) {
            Log-Msg "=========================================================="
            Log-Msg "SUCCESS! MitraNet OS booted from virtual disk!"
            Log-Msg "Assigned IP Address: $ips"
            Log-Msg "WebGUI available at: https://$ips"
            Log-Msg "=========================================================="
            Capture-VMScreen
            break
        }
    }
}

Log-Msg "=== Auto Installer Monitor Daemon Finished ==="

param(
    [string]$VMName = "LiveTest"
)

Stop-VM -Name $VMName -TurnOff -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2

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

Start-VM -Name $VMName -ErrorAction SilentlyContinue
Start-Sleep -Seconds 15

powershell -ExecutionPolicy Bypass -File "c:\MitraNet\vm\capture_screen.ps1" -VMName $VMName

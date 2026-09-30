$vm = "LiveTest"
$dvd = Get-VMDvdDrive -VMName $vm
$hdd = Get-VMHardDiskDrive -VMName $vm

Write-Host "[*] Setting boot order: DVD first..."
Set-VMFirmware -VMName $vm -BootOrder $dvd, $hdd

Write-Host "[*] Restarting $vm to boot from ISO..."
Restart-VM -VMName $vm -Force

Start-Sleep -Seconds 5
Get-VMFirmware -VMName $vm | Select-Object -ExpandProperty BootOrder
Get-VM -Name $vm

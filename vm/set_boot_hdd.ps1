$vm = "LiveTest"
$hdd = Get-VMHardDiskDrive -VMName $vm
$dvd = Get-VMDvdDrive -VMName $vm
Set-VMFirmware -VMName $vm -BootOrder $hdd, $dvd
Get-VMFirmware -VMName $vm | Select-Object -ExpandProperty BootOrder

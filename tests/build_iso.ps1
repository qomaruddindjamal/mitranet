$xorriso = "C:\ProgramData\chocolatey\bin\xorriso.exe"
if (!(Test-Path $xorriso)) {
    $xorriso = (Get-Command xorriso -ErrorAction SilentlyContinue).Source
}
Write-Host "Using xorriso: $xorriso"

$isoOut = "build\MitraNet-Rinjani-1.0.2-amd64.iso"
$isoWork = "build\iso_work"

& $xorriso -as mkisofs -r -V "MitraNet-Rinjani-1.0.2" -o $isoOut -J -joliet-long -b isolinux/isolinux.bin -c isolinux/boot.cat -no-emul-boot -boot-load-size 4 -boot-info-table -eltorito-alt-boot -e boot/grub/efi.img -no-emul-boot -isohybrid-gpt-basdat $isoWork

Get-Item $isoOut | Select-Object Name, Length, LastWriteTime

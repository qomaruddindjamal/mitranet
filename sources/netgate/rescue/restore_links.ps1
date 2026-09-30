# Recreate FreeBSD rescue crunchgen hardlinks on Windows NTFS before ISO compilation
$dir = Split-Path -Parent $MyInvocation.MyCommand.Path
if ((Test-Path "$dir\links.txt") -and (Test-Path "$dir\rescue")) {
    Write-Host "[*] Restoring rescue NTFS hardlinks from links.txt..." -ForegroundColor Yellow
    $links = Get-Content "$dir\links.txt"
    foreach ($link in $links) {
        $link = $link.Trim()
        if ($link -and -not (Test-Path "$dir\$link")) {
            New-Item -ItemType HardLink -Path "$dir\$link" -Value "$dir\rescue" -Force | Out-Null
        }
    }
    Write-Host "[+] Rescue NTFS hardlinks restored successfully." -ForegroundColor Green
}

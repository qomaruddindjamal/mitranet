param(
    [string]$VMName = "LiveTest"
)

$keys = @('enter', 'enter', 'down', 'enter', 'enter', 'enter', 'enter')
foreach ($k in $keys) {
    Write-Host "Sending key: $k"
    powershell -ExecutionPolicy Bypass -File "c:\MitraNet\vm\send_key.ps1" -VMName $VMName -Key $k
    Start-Sleep -Seconds 4
}

powershell -ExecutionPolicy Bypass -File "c:\MitraNet\vm\capture_screen.ps1" -VMName $VMName

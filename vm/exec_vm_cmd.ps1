param(
    [string]$VMName = "LiveTest",
    [string]$Command = ""
)

if (-not $Command) {
    Write-Error "Command is required."
    exit 1
}

$vm = Get-CimInstance -Namespace 'root\virtualization\v2' -ClassName Msvm_ComputerSystem -Filter "ElementName = '$VMName'"
if (-not $vm) {
    Write-Error "VM $VMName not found."
    exit 1
}

$kbd = Get-CimAssociatedInstance -InputObject $vm -ResultClassName Msvm_Keyboard

Write-Host "[*] Sending command to VM ${VMName}: $Command"

# Send text
$kbd | Invoke-CimMethod -MethodName TypeText -Arguments @{ AsciiText = $Command } | Out-Null
Start-Sleep -Milliseconds 300

# Send Enter
$kbd | Invoke-CimMethod -MethodName TypeKey -Arguments @{ KeyCode = [uint32]13 } | Out-Null
Start-Sleep -Seconds 2

# Capture screen
powershell -ExecutionPolicy Bypass -File .\vm\capture_screen.ps1 -VMName $VMName -OutputPath "livetest_screen.png" | Out-Null
Write-Host "[+] Command sent and screen captured."

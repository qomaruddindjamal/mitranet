param(
    [string]$VMName = "LiveTest",
    [string]$Cmd = ""
)

$vm = Get-CimInstance -Namespace 'root\virtualization\v2' -ClassName Msvm_ComputerSystem -Filter "ElementName = '$VMName'"
if (-not $vm) { exit 1 }

$kbd = Get-CimAssociatedInstance -InputObject $vm -ResultClassName Msvm_Keyboard
if (-not $kbd) { exit 1 }

if ($Cmd) {
    $kbd | Invoke-CimMethod -MethodName TypeText -Arguments @{ AsciiText = $Cmd } | Out-Null
    Start-Sleep -Milliseconds 300
    $kbd | Invoke-CimMethod -MethodName TypeKey -Arguments @{ KeyCode = [uint32]13 } | Out-Null
    Start-Sleep -Milliseconds 500
}

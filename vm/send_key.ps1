param(
    [string]$VMName = "LiveTest",
    [string]$Key = "enter" # enter, space, tab, left, right, up, down, y
)

$vm = Get-CimInstance -Namespace 'root\virtualization\v2' -ClassName Msvm_ComputerSystem -Filter "ElementName = '$VMName'"
if (-not $vm) {
    Write-Error "VM $VMName not found"
    exit 1
}

$kbd = Get-CimAssociatedInstance -InputObject $vm -ResultClassName Msvm_Keyboard

switch ($Key.ToLower()) {
    "enter" {
        $kbd | Invoke-CimMethod -MethodName TypeKey -Arguments @{ KeyCode = [uint32]13 } | Out-Null
    }
    "space" {
        $kbd | Invoke-CimMethod -MethodName TypeKey -Arguments @{ KeyCode = [uint32]32 } | Out-Null
    }
    "tab" {
        $kbd | Invoke-CimMethod -MethodName TypeKey -Arguments @{ KeyCode = [uint32]9 } | Out-Null
    }
    "esc" {
        $kbd | Invoke-CimMethod -MethodName TypeKey -Arguments @{ KeyCode = [uint32]27 } | Out-Null
    }
    "left" {
        $kbd | Invoke-CimMethod -MethodName TypeKey -Arguments @{ KeyCode = [uint32]37 } | Out-Null
    }
    "right" {
        $kbd | Invoke-CimMethod -MethodName TypeKey -Arguments @{ KeyCode = [uint32]39 } | Out-Null
    }
    "up" {
        $kbd | Invoke-CimMethod -MethodName TypeKey -Arguments @{ KeyCode = [uint32]38 } | Out-Null
    }
    "down" {
        $kbd | Invoke-CimMethod -MethodName TypeKey -Arguments @{ KeyCode = [uint32]40 } | Out-Null
    }
    default {
        $kbd | Invoke-CimMethod -MethodName TypeText -Arguments @{ AsciiText = $Key } | Out-Null
    }
}

Write-Host "Sent key: $Key to $VMName"

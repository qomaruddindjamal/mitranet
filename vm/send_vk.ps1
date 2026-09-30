param(
    [string]$VMName = "LiveTest",
    [string]$Text = ""
)

$vm = Get-CimInstance -Namespace 'root\virtualization\v2' -ClassName Msvm_ComputerSystem -Filter "ElementName = '$VMName'"
$kbd = Get-CimAssociatedInstance -InputObject $vm -ResultClassName Msvm_Keyboard

$VK_SHIFT = [uint32]16

foreach ($c in $Text.ToCharArray()) {
    $code = 0
    $shift = $false

    switch -Exact ($c) {
        ' '  { $code = 32 }
        "`n" { $code = 13 }
        "`r" { $code = 13 }
        "\t" { $code = 9 }
        '-'  { $code = 189; $shift = $false }
        '_'  { $code = 189; $shift = $true }
        '='  { $code = 187; $shift = $false }
        '+'  { $code = 187; $shift = $true }
        '.'  { $code = 190; $shift = $false }
        '>'  { $code = 190; $shift = $true }
        ','  { $code = 188; $shift = $false }
        '<'  { $code = 188; $shift = $true }
        '/'  { $code = 191; $shift = $false }
        '?'  { $code = 191; $shift = $true }
        ';'  { $code = 186; $shift = $false }
        ':'  { $code = 186; $shift = $true }
        "'"  { $code = 222; $shift = $false }
        '"'  { $code = 222; $shift = $true }
        '['  { $code = 219; $shift = $false }
        '{'  { $code = 219; $shift = $true }
        ']'  { $code = 221; $shift = $false }
        '}'  { $code = 221; $shift = $true }
        '\'  { $code = 220; $shift = $false }
        '|'  { $code = 220; $shift = $true }
        '`'  { $code = 192; $shift = $false }
        '~'  { $code = 192; $shift = $true }
        '!'  { $code = 49;  $shift = $true }
        '@'  { $code = 50;  $shift = $true }
        '#'  { $code = 51;  $shift = $true }
        '$'  { $code = 52;  $shift = $true }
        '%'  { $code = 53;  $shift = $true }
        '^'  { $code = 54;  $shift = $true }
        '&'  { $code = 55;  $shift = $true }
        '*'  { $code = 56;  $shift = $true }
        '('  { $code = 57;  $shift = $true }
        ')'  { $code = 48;  $shift = $true }
        default {
            if ($c -ge 'a' -and $c -le 'z') {
                $code = [int][char]($c.ToString().ToUpper())
                $shift = $false
            } elseif ($c -ge 'A' -and $c -le 'Z') {
                $code = [int][char]$c
                $shift = $true
            } elseif ($c -ge '0' -and $c -le '9') {
                $code = [int][char]$c
                $shift = $false
            }
        }
    }

    if ($code -gt 0) {
        if ($shift) {
            $kbd | Invoke-CimMethod -MethodName PressKey -Arguments @{ KeyCode = $VK_SHIFT } | Out-Null
            Start-Sleep -Milliseconds 15
        }
        $kbd | Invoke-CimMethod -MethodName TypeKey -Arguments @{ KeyCode = [uint32]$code } | Out-Null
        if ($shift) {
            Start-Sleep -Milliseconds 15
            $kbd | Invoke-CimMethod -MethodName ReleaseKey -Arguments @{ KeyCode = $VK_SHIFT } | Out-Null
        }
        Start-Sleep -Milliseconds 30
    }
}

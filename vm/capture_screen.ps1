param(
    [string]$VMName = "LiveTest",
    [string]$OutputFile = "C:\Users\Administrator\.gemini\antigravity-ide\brain\ed3bbe78-4d4d-47d4-917e-c020dfdf08bb\livetest_screen.png",
    [string]$RawFile = "c:\MitraNet\vm\scratch_thumb.raw"
)

$vmObj = Get-CimInstance -Namespace 'root\virtualization\v2' -ClassName Msvm_ComputerSystem -Filter "ElementName = '$VMName'"
if (-not $vmObj) {
    Write-Error "VM $VMName not found."
    exit 1
}

$vsms = Get-CimInstance -Namespace 'root\virtualization\v2' -ClassName Msvm_VirtualSystemManagementService
$vssd = Get-CimAssociatedInstance -InputObject $vmObj -ResultClassName Msvm_VirtualSystemSettingData | Where-Object { $_.VirtualSystemType -eq 'Microsoft:Hyper-V:System:Realized' }
if (-not $vssd) {
    $vssd = (Get-CimAssociatedInstance -InputObject $vmObj -ResultClassName Msvm_VirtualSystemSettingData)[0]
}

$res = Invoke-CimMethod -InputObject $vsms -MethodName GetVirtualSystemThumbnailImage -Arguments @{
    TargetSystem = [Microsoft.Management.Infrastructure.CimInstance]$vssd
    WidthPixels = [uint16]640
    HeightPixels = [uint16]480
}

if ($res.ImageData) {
    [System.IO.File]::WriteAllBytes($RawFile, $res.ImageData)
    $pyScript = "import struct; from PIL import Image; f=open(r'$RawFile','rb'); d=f.read()[4:]; f.close(); img=Image.new('RGB',(640,480)); px=[(((v>>11)&0x1F)<<3, ((v>>5)&0x3F)<<2, (v&0x1F)<<3) for v in struct.unpack('<'+'H'*(len(d)//2), d)]; img.putdata(px); img.save(r'$OutputFile')"
    python -c $pyScript 2>$null
    Write-Host "Screenshot captured to $OutputFile"
} else {
    Write-Host "No image data returned from Hyper-V"
}

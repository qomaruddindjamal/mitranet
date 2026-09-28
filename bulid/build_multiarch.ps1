# MitraNet - PowerShell Multi-Architecture Builder
param(
    [string]$Target = "x86_64Bit",
    [string]$OutputDir = "images\releases"
)

$ErrorActionPreference = "Stop"

Write-Host "===================================================================" -ForegroundColor Cyan
Write-Host " MitraNet Multi-Architecture Generator (PowerShell)" -ForegroundColor Cyan
Write-Host " Target Architecture: $Target" -ForegroundColor Cyan
Write-Host "===================================================================" -ForegroundColor Cyan

New-Item -ItemType Directory -Force -Path $OutputDir | Out-Null

function Normalize-Target([string]$t) {
    switch ($t.ToLower()) {
        "x86_64bit" { return "x86_64" }
        "x86_64"    { return "x86_64" }
        "amd64"     { return "x86_64" }
        "arm64"     { return "arm64" }
        "aarch64"   { return "arm64" }
        "arm"       { return "arm" }
        "armv7"     { return "arm" }
        "mipsbe"    { return "mipsbe" }
        "mmips"     { return "mmips" }
        "smips"     { return "smips" }
        "mipsle"    { return "mipsle" }
        "ppc"       { return "ppc" }
        "all singleboard" { return "sbc_all" }
        "sbc"       { return "sbc_all" }
        "silicon"   { return "silicon" }
        "vm"        { return "vm_all" }
        "all"       { return "all" }
        default     { return $t }
    }
}

$normTarget = Normalize-Target $Target
$profiles = @()

if ($normTarget -eq "all") {
    $profiles = @("x86_64", "arm64", "arm", "mipsbe", "mmips", "smips", "mipsle", "ppc", "sbc_all", "silicon", "vm_all")
} else {
    $profiles = @($normTarget)
}

foreach ($arch in $profiles) {
    $profilePath = "profiles\$arch.json"
    if (-not (Test-Path $profilePath)) {
        Write-Warning "Profile not found: $profilePath"
        continue
    }

    $prof = Get-Content $profilePath | ConvertFrom-Json
    Write-Host ""
    Write-Host ">>> Processing Target: [$arch] - $($prof.name)" -ForegroundColor Yellow
    Write-Host "    Target Format: $($prof.target_format) | Memory Profile: $($prof.memory_profile)"

    $outArtifact = "$OutputDir\MitraNet-$arch-bundle.zip"
    Write-Host "[*] Packaging bundle -> $outArtifact"
    Compress-Archive -Path "programs\xray\config\*.json", "programs\xray\service\*", "programs\xray\cli\*" -DestinationPath $outArtifact -Force
    
    $hash = Get-FileHash $outArtifact -Algorithm SHA256
    $hash.Hash | Out-File -FilePath "$outArtifact.sha256" -Encoding ascii
    Write-Host "[+] Created: $outArtifact (SHA256: $($hash.Hash.Substring(0, 16))...)" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== Multi-Architecture Build Completed ===" -ForegroundColor Green
Get-ChildItem $OutputDir

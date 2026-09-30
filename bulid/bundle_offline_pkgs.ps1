param(
    [string]$TargetDir = "",
    [string]$PkgSourceDir = "bulid\packages_cache"
)

$ErrorActionPreference = "Stop"

if (-not $TargetDir) {
    if (Test-Path "sources\netgate\etc") {
        $TargetDir = "sources\netgate"
    } else {
        $TargetDir = "bulid\iso_root"
    }
}

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " [MitraNet] Offline Package Bundler (PowerShell)" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

$pkgDest = "$TargetDir\packages\All"
$repoConfDir = "$TargetDir\usr\local\etc\pkg\repos"

New-Item -ItemType Directory -Force -Path $pkgDest | Out-Null
New-Item -ItemType Directory -Force -Path $repoConfDir | Out-Null
New-Item -ItemType Directory -Force -Path $PkgSourceDir | Out-Null

Write-Host "[*] Checking for cached packages in: $PkgSourceDir" -ForegroundColor Yellow

$pkgs = Get-ChildItem -Path $PkgSourceDir, $pkgDest -Filter "*.pkg" -ErrorAction SilentlyContinue

if ($pkgs.Count -gt 0) {
    Write-Host "[*] Found $($pkgs.Count) offline packages. Ensuring in $pkgDest..." -ForegroundColor Yellow
    foreach ($p in $pkgs) {
        if ($p.DirectoryName -ne (Get-Item $pkgDest).FullName) {
            Copy-Item $p.FullName $pkgDest -Force
        }
    }
} else {
    Write-Host "[!] No .pkg files found in $PkgSourceDir." -ForegroundColor DarkYellow
    Write-Host "    To extract packages from VM, run inside the VM terminal:" -ForegroundColor Cyan
    Write-Host "      mkdir -p /tmp/pkgs && pkg create -a -o /tmp/pkgs/" -ForegroundColor White
    Write-Host "    Or copy from /var/cache/pkg/*.pkg to $PkgSourceDir" -ForegroundColor Cyan
}

# Generate Offline Repository Configuration
Write-Host "[*] Writing offline pkg repository configuration..." -ForegroundColor Yellow
$offlineConf = @"
MitraNet-Offline: {
  url: "file:///packages",
  mirror_type: "NONE",
  enabled: yes
}

FreeBSD: {
  enabled: no
}

pfSense: {
  enabled: no
}
"@

$offlineConf | Out-File -FilePath "$repoConfDir\MitraNet-offline.conf" -Encoding ascii -Force

# Optional High Compression Archive for Packages
$bundleArchive = "$TargetDir\packages\packages_bundle.tar.xz"
if ($pkgs.Count -gt 0) {
    Write-Host "[*] Compressing offline packages with LZMA2 Extreme (Maximum Compression)..." -ForegroundColor Yellow
    $tarTemp = "$TargetDir\packages\packages_temp.tar"
    & 7z a -ttar "$tarTemp" "$pkgDest\*.pkg" | Out-Null
    & 7z a -txz -mx=9 -md=64m -mfb=273 "$bundleArchive" "$tarTemp" | Out-Null
    Remove-Item -Force "$tarTemp" -ErrorAction SilentlyContinue
    if (Test-Path $bundleArchive) {
        $arcMB = [math]::Round((Get-Item $bundleArchive).Length / 1MB, 2)
        Write-Host "[+] Maximum compressed bundle created: $bundleArchive ($arcMB MB)" -ForegroundColor Green
    }
}

Write-Host "[+] Offline repository configuration generated at:" -ForegroundColor Green
Write-Host "    $repoConfDir\MitraNet-offline.conf" -ForegroundColor Green
Write-Host "[+] All offline packages ready in: $pkgDest" -ForegroundColor Green

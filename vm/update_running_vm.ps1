# Update running VM with new WebGUI and API
$cmds = @(
    "fetch -o /usr/local/www/web-installer/index.html http://172.21.160.1:8000/dashboard.html`n",
    "fetch -o /usr/local/bin/mitranet-web http://172.21.160.1:8000/mitranet_api.py`n",
    "chmod 755 /usr/local/bin/mitranet-web`n",
    "killall -9 mitranet-web 2>/dev/null; /usr/local/bin/mitranet-web > /var/log/mitranet-api.log 2>&1 &`n",
    "service nginx restart`n"
)

foreach ($cmd in $cmds) {
    powershell -ExecutionPolicy Bypass -File .\vm\send_vk.ps1 -VMName "LiveTest" -Text $cmd
    Start-Sleep -Seconds 2
}

powershell -ExecutionPolicy Bypass -File .\vm\capture_screen.ps1 -VMName "LiveTest" -OutputPath "livetest_screen.png"

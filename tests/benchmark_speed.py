import paramiko
import json
import time

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=10)

# 1. Ping test
print("--- 1. PING LATENCY & JITTER ---")
_, stdout, _ = ssh.exec_command("ping -c 10 -i 0.2 10.10.77.1", timeout=15)
print(stdout.read().decode().strip())

# 2. Public IP verify
print("\n--- 2. PUBLIC IP VERIFICATION ---")
_, stdout, _ = ssh.exec_command("curl --interface 10.10.77.3 -s --max-time 10 https://api.ipify.org; echo ''", timeout=15)
print(stdout.read().decode().strip())

# 3. Direct HTTP Throughput via WireGuard (10MB test)
print("\n--- 3. WIRE GUARD DIRECT DOWNLOAD TEST (10MB payload) ---")
test_cmd = "curl --interface 10.10.77.3 -o /dev/null -s -w 'Speed: %{speed_download} B/s, Time: %{time_total}s\n' --max-time 45 https://speed.cloudflare.com/__down?bytes=10000000"
_, stdout, _ = ssh.exec_command(test_cmd, timeout=50)
print(stdout.read().decode().strip())

# 4. Compare with Direct Physical WAN (eth0 / non-tunnel)
print("\n--- 4. DIRECT PHYSICAL WAN (NON-TUNNEL) COMPARISON ---")
wan_cmd = "curl -o /dev/null -s -w 'Speed: %{speed_download} B/s, Time: %{time_total}s\n' --max-time 45 https://speed.cloudflare.com/__down?bytes=10000000"
_, stdout, _ = ssh.exec_command(wan_cmd, timeout=50)
print(stdout.read().decode().strip())

ssh.close()

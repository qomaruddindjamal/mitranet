import paramiko
import time
import json

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8').strip()
    err = stderr.read().decode('utf-8').strip()
    return out, err

print("=== 1. VERIFIKASI INTERFACE COUNTER AWAL ===")
for iface in ['wg0', 'wgboost1', 'wgboost2']:
    rx, _ = run(f"cat /sys/class/net/{iface}/statistics/rx_bytes")
    tx, _ = run(f"cat /sys/class/net/{iface}/statistics/tx_bytes")
    print(f"{iface}: rx={rx} bytes, tx={tx} bytes")

print("\n=== 2. BENCHMARK CURL BOUND TO INTERFACE ===")
# Tes baseline: bind ke wg0
out, err = run("curl -s --interface wg0 -w 'Time: %{time_total}s | Speed: %{speed_download} bytes/s | HTTP: %{http_code}' -o /dev/null http://speed.cloudflare.com/__down?bytes=50000000")
print("Baseline via wg0 (interface):", out, err)

# Tes wgboost1: bind ke wgboost1
out, err = run("curl -s --interface wgboost1 -w 'Time: %{time_total}s | Speed: %{speed_download} bytes/s | HTTP: %{http_code}' -o /dev/null http://speed.cloudflare.com/__down?bytes=50000000")
print("Booster via wgboost1 (interface):", out, err)

# Tes wgboost2: bind ke wgboost2
out, err = run("curl -s --interface wgboost2 -w 'Time: %{time_total}s | Speed: %{speed_download} bytes/s | HTTP: %{http_code}' -o /dev/null http://speed.cloudflare.com/__down?bytes=50000000")
print("Booster via wgboost2 (interface):", out, err)

print("\n=== 3. VERIFIKASI INTERFACE COUNTER AKHIR ===")
for iface in ['wg0', 'wgboost1', 'wgboost2']:
    rx, _ = run(f"cat /sys/class/net/{iface}/statistics/rx_bytes")
    tx, _ = run(f"cat /sys/class/net/{iface}/statistics/tx_bytes")
    print(f"{iface}: rx={rx} bytes, tx={tx} bytes")

ssh.close()

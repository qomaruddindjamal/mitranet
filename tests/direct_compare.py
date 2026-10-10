import paramiko
import time

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    return stdout.read().decode('utf-8').strip(), stderr.read().decode('utf-8').strip()

print("=================================================================")
print("BENCHMARK COMPARISON: WG0 (BASELINE) vs WGBOOST1 vs WGBOOST2")
print("=================================================================")

for dev, ip in [('wg0', '10.10.77.3'), ('wgboost1', '10.250.1.2'), ('wgboost2', '10.250.2.2')]:
    rx_before = int(run(f"cat /sys/class/net/{dev}/statistics/rx_bytes")[0])
    tx_before = int(run(f"cat /sys/class/net/{dev}/statistics/tx_bytes")[0])
    
    t0 = time.time()
    url = "'http://speed.cloudflare.com/__down?bytes=25000000'"
    fmt = "'%{speed_download},%{time_total}'"
    out, err = run(f"curl -4 -s -m 20 --interface {dev} -w {fmt} -o /dev/null {url}")
    dur_local = time.time() - t0
    
    rx_after = int(run(f"cat /sys/class/net/{dev}/statistics/rx_bytes")[0])
    tx_after = int(run(f"cat /sys/class/net/{dev}/statistics/tx_bytes")[0])
    
    d_rx = rx_after - rx_before
    d_tx = tx_after - tx_before
    
    speed_bps = 0
    time_curl = dur_local
    if out and ',' in out:
        parts = out.split(',')
        try:
            speed_bps = float(parts[0]) * 8
            time_curl = float(parts[1])
        except Exception:
            pass
            
    mbps_curl = speed_bps / 1_000_000
    mbps_rx = (d_rx * 8) / (dur_local * 1_000_000)
    
    print(f"Interface: {dev} ({ip})")
    print(f"  Curl Output: {out} (err: {err if err else 'none'})")
    print(f"  Throughput: {mbps_curl:.2f} Mbps (Curl), {mbps_rx:.2f} Mbps (RX counter)")
    print(f"  Delta: Rx=+{d_rx/1024/1024:.2f} MB, Tx=+{d_tx/1024/1024:.2f} MB in {dur_local:.2f}s")
    print("-----------------------------------------------------------------")

ssh.close()

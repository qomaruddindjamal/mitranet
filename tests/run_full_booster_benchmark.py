import paramiko
import time
import json

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    return stdout.read().decode('utf-8').strip(), stderr.read().decode('utf-8').strip()

print("=================================================================")
print("EVALUASI KOMPREHENSIF PERFORMA CLOUD SPEED BOOSTER MITRANET")
print("=================================================================")

# Pastikan default route aman sebelum tes
run("ip route replace default dev wg0 table 51820")
run("iptables -t nat -A POSTROUTING -o wgboost1 -j MASQUERADE 2>/dev/null")
run("iptables -t nat -A POSTROUTING -o wgboost2 -j MASQUERADE 2>/dev/null")
run("wg set wgboost1 fwmark 0xca6c peer V8T7f3Ec13e1imlbU/bfH4q/IEkcoM3YtZtIkSyO6y8= allowed-ips 0.0.0.0/0")
run("wg set wgboost2 fwmark 0xca6c peer e+BxtRvJQvMLdUbTRCCEA5RpTNuyngIK3jQIj8gEPX8= allowed-ips 0.0.0.0/0")

# 1. BASELINE TEST (wg0)
print("\n--- 1. BASELINE (wg0) ---")
run("ip route replace default dev wg0 table 51820")
time.sleep(1)
ping_res, _ = run("ping -c 4 8.8.8.8")
print("Ping 8.8.8.8:\n", ping_res.splitlines()[-1] if ping_res else "ERROR")

rx0_wg0 = int(run("cat /sys/class/net/wg0/statistics/rx_bytes")[0])
tx0_wg0 = int(run("cat /sys/class/net/wg0/statistics/tx_bytes")[0])

st_wg0, _ = run("speedtest-cli --server 75631 --simple")
print("Speedtest Result (wg0):\n", st_wg0)

rx1_wg0 = int(run("cat /sys/class/net/wg0/statistics/rx_bytes")[0])
tx1_wg0 = int(run("cat /sys/class/net/wg0/statistics/tx_bytes")[0])
print(f"wg0 Counter: Rx=+{(rx1_wg0-rx0_wg0)/1024/1024:.2f} MB, Tx=+{(tx1_wg0-tx0_wg0)/1024/1024:.2f} MB")

# 2. SINGLE-STREAM BOOSTER TEST (wgboost1)
print("\n--- 2. BOOSTER SINGLE-STREAM (wgboost1) ---")
run("ip route replace default dev wgboost1 table 51820")
time.sleep(1)
ping_b1, _ = run("ping -c 4 8.8.8.8")
print("Ping 8.8.8.8:\n", ping_b1.splitlines()[-1] if ping_b1 else "ERROR")

rx0_b1 = int(run("cat /sys/class/net/wgboost1/statistics/rx_bytes")[0])
tx0_b1 = int(run("cat /sys/class/net/wgboost1/statistics/tx_bytes")[0])

st_b1, _ = run("speedtest-cli --server 75631 --simple")
print("Speedtest Result (wgboost1):\n", st_b1)

rx1_b1 = int(run("cat /sys/class/net/wgboost1/statistics/rx_bytes")[0])
tx1_b1 = int(run("cat /sys/class/net/wgboost1/statistics/tx_bytes")[0])
print(f"wgboost1 Counter: Rx=+{(rx1_b1-rx0_b1)/1024/1024:.2f} MB, Tx=+{(tx1_b1-tx0_b1)/1024/1024:.2f} MB")

# 3. DUAL-STREAM BOOSTER TEST (wgboost1 + wgboost2 ECMP Multipath)
print("\n--- 3. BOOSTER DUAL-STREAM (ECMP wgboost1 + wgboost2) ---")
run("ip route replace default nexthop dev wgboost1 weight 1 nexthop dev wgboost2 weight 1 table 51820")
time.sleep(1)
ping_ecmp, _ = run("ping -c 4 8.8.8.8")
print("Ping 8.8.8.8:\n", ping_ecmp.splitlines()[-1] if ping_ecmp else "ERROR")

rx0_m1 = int(run("cat /sys/class/net/wgboost1/statistics/rx_bytes")[0])
rx0_m2 = int(run("cat /sys/class/net/wgboost2/statistics/rx_bytes")[0])
tx0_m1 = int(run("cat /sys/class/net/wgboost1/statistics/tx_bytes")[0])
tx0_m2 = int(run("cat /sys/class/net/wgboost2/statistics/tx_bytes")[0])

st_ecmp, _ = run("speedtest-cli --server 75631 --simple")
print("Speedtest Result (Dual-Stream ECMP):\n", st_ecmp)

rx1_m1 = int(run("cat /sys/class/net/wgboost1/statistics/rx_bytes")[0])
rx1_m2 = int(run("cat /sys/class/net/wgboost2/statistics/rx_bytes")[0])
tx1_m1 = int(run("cat /sys/class/net/wgboost1/statistics/tx_bytes")[0])
tx1_m2 = int(run("cat /sys/class/net/wgboost2/statistics/tx_bytes")[0])

diff_m1_rx = (rx1_m1 - rx0_m1) / 1024 / 1024
diff_m2_rx = (rx1_m2 - rx0_m2) / 1024 / 1024
print(f"wgboost1 Counter: Rx=+{diff_m1_rx:.2f} MB, Tx=+{(tx1_m1-tx0_m1)/1024/1024:.2f} MB")
print(f"wgboost2 Counter: Rx=+{diff_m2_rx:.2f} MB, Tx=+{(tx1_m2-tx0_m2)/1024/1024:.2f} MB")
print(f"Total Agregat Rx: {diff_m1_rx + diff_m2_rx:.2f} MB")

# Kembalikan routing default ke wg0 di table 51820
run("ip route replace default dev wg0 table 51820")
print("\n[INFO] Routing table 51820 telah dikembalikan ke default dev wg0.")

ssh.close()

import paramiko
import time
import json
import re

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    return stdout.read().decode('utf-8').strip(), stderr.read().decode('utf-8').strip()

def get_stats(iface):
    rx = int(run(f"cat /sys/class/net/{iface}/statistics/rx_bytes")[0])
    tx = int(run(f"cat /sys/class/net/{iface}/statistics/tx_bytes")[0])
    rx_drop = int(run(f"cat /sys/class/net/{iface}/statistics/rx_dropped")[0])
    tx_drop = int(run(f"cat /sys/class/net/{iface}/statistics/tx_dropped")[0])
    return {'rx': rx, 'tx': tx, 'rx_drop': rx_drop, 'tx_drop': tx_drop}

def get_tcp_retrans():
    out, _ = run("netstat -s | grep -i 'segments retransmitted'")
    m = re.search(r'(\d+)', out)
    return int(m.group(1)) if m else 0

print("==========================================================================")
print("ISOLASI BOTTLENECK DUAL-STREAM ECMP: BENCHMARK 4 SKENARIO TERKONTROL")
print("==========================================================================")

# Persiapan awal: pastikan fwmark & NAT aktif
run("wg set wgboost1 fwmark 0xca6c peer V8T7f3Ec13e1imlbU/bfH4q/IEkcoM3YtZtIkSyO6y8= allowed-ips 0.0.0.0/0")
run("wg set wgboost2 fwmark 0xca6c peer e+BxtRvJQvMLdUbTRCCEA5RpTNuyngIK3jQIj8gEPX8= allowed-ips 0.0.0.0/0")
run("iptables -t nat -A POSTROUTING -o wgboost1 -j MASQUERADE 2>/dev/null")
run("iptables -t nat -A POSTROUTING -o wgboost2 -j MASQUERADE 2>/dev/null")
run("sysctl -w net.ipv4.fib_multipath_hash_policy=1")

# SKENARIO A: BASELINE wg0
print("\n[A] SKENARIO A: BASELINE (wg0 murni)")
run("ip route replace default dev wg0 table 51820")
time.sleep(1)
ping_a, _ = run("ping -c 4 -W 2 8.8.8.8")
r_before_a = get_tcp_retrans()
s_wg0_0 = get_stats('wg0')

st_a, _ = run("speedtest-cli --server 75631 --simple")
r_after_a = get_tcp_retrans()
s_wg0_1 = get_stats('wg0')

print("Ping:", ping_a.splitlines()[-1] if ping_a else "ERR")
print("Hasil Speedtest:\n", st_a)
print(f"Delta wg0: Rx=+{(s_wg0_1['rx']-s_wg0_0['rx'])/1024/1024:.2f} MB, Tx=+{(s_wg0_1['tx']-s_wg0_0['tx'])/1024/1024:.2f} MB, Drop={s_wg0_1['tx_drop']-s_wg0_0['tx_drop']}")
print(f"TCP Retransmit Delta: {r_after_a - r_before_a}")

# SKENARIO B: BOOSTER wgboost1 TUNGGAL
print("\n[B] SKENARIO B: BOOSTER SINGLE-STREAM (wgboost1)")
run("ip route replace default dev wgboost1 table 51820")
time.sleep(1)
ping_b, _ = run("ping -c 4 -W 2 8.8.8.8")
r_before_b = get_tcp_retrans()
s_b1_0 = get_stats('wgboost1')

st_b, _ = run("speedtest-cli --server 75631 --simple")
r_after_b = get_tcp_retrans()
s_b1_1 = get_stats('wgboost1')

print("Ping:", ping_b.splitlines()[-1] if ping_b else "ERR")
print("Hasil Speedtest:\n", st_b)
print(f"Delta wgboost1: Rx=+{(s_b1_1['rx']-s_b1_0['rx'])/1024/1024:.2f} MB, Tx=+{(s_b1_1['tx']-s_b1_0['tx'])/1024/1024:.2f} MB, Drop={s_b1_1['tx_drop']-s_b1_0['tx_drop']}")
print(f"TCP Retransmit Delta: {r_after_b - r_before_b}")

# SKENARIO C: DUAL TUNNEL AKTIF, 1 SINGLE-FLOW TEST
print("\n[C] SKENARIO C: DUAL-STREAM ECMP (Single-Flow Speedtest)")
run("ip route replace default table 51820 nexthop dev wgboost1 weight 1 nexthop dev wgboost2 weight 1")
time.sleep(1)
ping_c, _ = run("ping -c 4 -W 2 8.8.8.8")
r_before_c = get_tcp_retrans()
sc_b1_0 = get_stats('wgboost1')
sc_b2_0 = get_stats('wgboost2')

st_c, _ = run("speedtest-cli --server 75631 --simple")
r_after_c = get_tcp_retrans()
sc_b1_1 = get_stats('wgboost1')
sc_b2_1 = get_stats('wgboost2')

print("Ping:", ping_c.splitlines()[-1] if ping_c else "ERR")
print("Hasil Speedtest:\n", st_c)
print(f"Delta wgboost1: Rx=+{(sc_b1_1['rx']-sc_b1_0['rx'])/1024/1024:.2f} MB, Tx=+{(sc_b1_1['tx']-sc_b1_0['tx'])/1024/1024:.2f} MB, Drop={sc_b1_1['tx_drop']-sc_b1_0['tx_drop']}")
print(f"Delta wgboost2: Rx=+{(sc_b2_1['rx']-sc_b2_0['rx'])/1024/1024:.2f} MB, Tx=+{(sc_b2_1['tx']-sc_b2_0['tx'])/1024/1024:.2f} MB, Drop={sc_b2_1['tx_drop']-sc_b2_0['tx_drop']}")
print(f"TCP Retransmit Delta: {r_after_c - r_before_c}")

# SKENARIO D: DUAL TUNNEL AKTIF, MULTIPLE PARALLEL INDEPENDENT FLOWS
print("\n[D] SKENARIO D: DUAL-STREAM ECMP (Multiple Independent Parallel Flows)")
time.sleep(1)
r_before_d = get_tcp_retrans()
sd_b1_0 = get_stats('wgboost1')
sd_b2_0 = get_stats('wgboost2')

# 4 unduhan paralel simultan ke host/port yang mendistribusikan hash L4
cmd_par = """
(curl -4 -s "http://speed.cloudflare.com/__down?bytes=5000000" -o /dev/null & \
 curl -4 -s "http://speed.cloudflare.com/__down?bytes=5000000" -o /dev/null & \
 curl -4 -s "http://speed.cloudflare.com/__down?bytes=5000000" -o /dev/null & \
 curl -4 -s "http://speed.cloudflare.com/__down?bytes=5000000" -o /dev/null & \
 wait)
"""
t0 = time.time()
run(cmd_par)
dur_d = time.time() - t0
r_after_d = get_tcp_retrans()
sd_b1_1 = get_stats('wgboost1')
sd_b2_1 = get_stats('wgboost2')

rx_d1 = (sd_b1_1['rx'] - sd_b1_0['rx']) / 1024 / 1024
rx_d2 = (sd_b2_1['rx'] - sd_b2_0['rx']) / 1024 / 1024
total_rx_d = rx_d1 + rx_d2
mbps_d = (total_rx_d * 8) / dur_d

print(f"Durasi Paralel: {dur_d:.2f} detik")
print(f"Delta wgboost1: Rx=+{rx_d1:.2f} MB, Tx=+{(sd_b1_1['tx']-sd_b1_0['tx'])/1024/1024:.2f} MB, Drop={sd_b1_1['tx_drop']-sd_b1_0['tx_drop']}")
print(f"Delta wgboost2: Rx=+{rx_d2:.2f} MB, Tx=+{(sd_b2_1['tx']-sd_b2_0['tx'])/1024/1024:.2f} MB, Drop={sd_b2_1['tx_drop']-sd_b2_0['tx_drop']}")
print(f"Total Agregat Rx: {total_rx_d:.2f} MB | Throughput Agregat: {mbps_d:.2f} Mbps")
print(f"TCP Retransmit Delta: {r_after_d - r_before_d}")

# Rollback ke default dev wg0
run("ip route replace default dev wg0 table 51820")
print("\n[INFO] Rollback selesai: Routing table 51820 telah dikembalikan ke default dev wg0.")

ssh.close()

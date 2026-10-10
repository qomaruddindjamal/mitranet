import paramiko
import time

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    return stdout.read().decode('utf-8').strip(), stderr.read().decode('utf-8').strip()

# Aktifkan L4 hashing (IP + Port) untuk multipath ECMP
run("sysctl -w net.ipv4.fib_multipath_hash_policy=1")
run("ip route replace default nexthop dev wgboost1 weight 1 nexthop dev wgboost2 weight 1 table 51820")

rx0_1 = int(run("cat /sys/class/net/wgboost1/statistics/rx_bytes")[0])
rx0_2 = int(run("cat /sys/class/net/wgboost2/statistics/rx_bytes")[0])

# Jalankan 4 paralel request ke endpoint cloudflare
t0 = time.time()
cmd_par = """
(curl -4 -s "http://speed.cloudflare.com/__down?bytes=10000000" -o /dev/null & \
 curl -4 -s "http://speed.cloudflare.com/__down?bytes=10000000" -o /dev/null & \
 curl -4 -s "http://speed.cloudflare.com/__down?bytes=10000000" -o /dev/null & \
 curl -4 -s "http://speed.cloudflare.com/__down?bytes=10000000" -o /dev/null & \
 wait)
"""
run(cmd_par)
dur = time.time() - t0

rx1_1 = int(run("cat /sys/class/net/wgboost1/statistics/rx_bytes")[0])
rx1_2 = int(run("cat /sys/class/net/wgboost2/statistics/rx_bytes")[0])

d1 = (rx1_1 - rx0_1) / 1024 / 1024
d2 = (rx1_2 - rx0_2) / 1024 / 1024
total_rx = d1 + d2
mbps = (total_rx * 8) / dur

print(f"Durasi Paralel: {dur:.2f} detik")
print(f"wgboost1 Rx Delta: {d1:.2f} MB")
print(f"wgboost2 Rx Delta: {d2:.2f} MB")
print(f"Total Rx Delta: {total_rx:.2f} MB")
print(f"Throughput Agregat: {mbps:.2f} Mbps")

run("ip route replace default dev wg0 table 51820")
ssh.close()

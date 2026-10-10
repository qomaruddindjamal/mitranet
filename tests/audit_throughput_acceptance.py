import paramiko
import time
import json
import sys

VPS_HOST = '103.93.162.168'
VPS_USER = 'citramedia'
VPS_PASS = 'K0323205'

MINI_HOST = '10.10.66.228'
MINI_USER = 'root'
MINI_PASS = 'mitranet'

def run_vps(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    return stdout.read().decode().strip(), stderr.read().decode().strip()

def run_mini(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    return stdout.read().decode().strip(), stderr.read().decode().strip()

def main():
    print("=====================================================================")
    print("AUDIT & VALIDASI PRIORITAS 1: THROUGHPUT NYATA & PARALEL FLOWS")
    print("=====================================================================")

    mini_ssh = paramiko.SSHClient()
    mini_ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    mini_ssh.connect(MINI_HOST, username=MINI_USER, password=MINI_PASS, timeout=5)

    vps_ssh = paramiko.SSHClient()
    vps_ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    vps_ssh.connect(VPS_HOST, port=22, username=VPS_USER, password=VPS_PASS, look_for_keys=False, allow_agent=False, timeout=8)

    # 1. SETUP 2 STREAMS (Stream 1 on 51831, Stream 2 on 51832)
    print("\n--- [A] Menyiapkan Stream 1 dan Stream 2 ---")
    # Mini PC keys
    run_mini(mini_ssh, "wg genkey | tee /tmp/k1_priv | wg pubkey > /tmp/k1_pub")
    run_mini(mini_ssh, "wg genkey | tee /tmp/k2_priv | wg pubkey > /tmp/k2_pub")
    c_pub1, _ = run_mini(mini_ssh, "cat /tmp/k1_pub")
    c_priv1, _ = run_mini(mini_ssh, "cat /tmp/k1_priv")
    c_pub2, _ = run_mini(mini_ssh, "cat /tmp/k2_pub")
    c_priv2, _ = run_mini(mini_ssh, "cat /tmp/k2_priv")

    # VPS setup stream 1
    run_vps(vps_ssh, '/interface/wireguard/peers/remove [find where interface=wg-boost1]')
    run_vps(vps_ssh, f'/interface wireguard peers add interface=wg-boost1 public-key="{c_pub1}" allowed-address=10.250.1.2/32 comment="Booster Peer 1"')
    vps_pk1, _ = run_vps(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost1] public-key]')

    # VPS setup stream 2
    run_vps(vps_ssh, '/interface/wireguard/peers/remove [find where interface=wg-boost2]')
    run_vps(vps_ssh, f'/interface wireguard peers add interface=wg-boost2 public-key="{c_pub2}" allowed-address=10.250.2.2/32 comment="Booster Peer 2"')
    vps_pk2, _ = run_vps(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost2] public-key]')

    # Mini PC bring up wgboost1 and wgboost2
    run_mini(mini_ssh, "wg-quick down wgboost1 2>/dev/null; ip link del wgboost1 2>/dev/null")
    run_mini(mini_ssh, "wg-quick down wgboost2 2>/dev/null; ip link del wgboost2 2>/dev/null")

    conf1 = f"""[Interface]
Address = 10.250.1.2/30
ListenPort = 51831
PrivateKey = {c_priv1}
MTU = 1420

[Peer]
PublicKey = {vps_pk1}
Endpoint = {VPS_HOST}:51831
AllowedIPs = 10.250.1.0/30
PersistentKeepalive = 10
"""
    conf2 = f"""[Interface]
Address = 10.250.2.2/30
ListenPort = 51832
PrivateKey = {c_priv2}
MTU = 1420

[Peer]
PublicKey = {vps_pk2}
Endpoint = {VPS_HOST}:51832
AllowedIPs = 10.250.2.0/30
PersistentKeepalive = 10
"""
    run_mini(mini_ssh, f"cat << 'EOF' > /etc/wireguard/wgboost1.conf\n{conf1}\nEOF")
    run_mini(mini_ssh, f"cat << 'EOF' > /etc/wireguard/wgboost2.conf\n{conf2}\nEOF")
    run_mini(mini_ssh, "chmod 600 /etc/wireguard/wgboost*.conf")
    run_mini(mini_ssh, "wg-quick up wgboost1")
    run_mini(mini_ssh, "wg-quick up wgboost2")

    time.sleep(2)
    p1, _ = run_mini(mini_ssh, "ping -c 3 -W 2 10.250.1.1")
    p2, _ = run_mini(mini_ssh, "ping -c 3 -W 2 10.250.2.1")
    print("Stream 1 Ping:", p1.splitlines()[-1] if p1 else "ERR")
    print("Stream 2 Ping:", p2.splitlines()[-1] if p2 else "ERR")

    # 2. UJI SINGLE-STREAM THROUGHPUT VIA WG TUNNEL 1
    print("\n--- [B] Uji Throughput Single-Stream (Tunnel 10.250.1.1) ---")
    s1_rx0, _ = run_mini(mini_ssh, "cat /sys/class/net/wgboost1/statistics/rx_bytes")
    s1_tx0, _ = run_mini(mini_ssh, "cat /sys/class/net/wgboost1/statistics/tx_bytes")

    t1_out, _ = run_mini(mini_ssh, "curl -s --interface 10.250.1.2 -w 'Time: %{time_total}s | Speed: %{speed_download} bytes/s | HTTP: %{http_code}' -o /dev/null http://speed.cloudflare.com/__down?bytes=50000000")
    print("Download 50MB via Stream 1:", t1_out)

    s1_rx1, _ = run_mini(mini_ssh, "cat /sys/class/net/wgboost1/statistics/rx_bytes")
    s1_tx1, _ = run_mini(mini_ssh, "cat /sys/class/net/wgboost1/statistics/tx_bytes")
    d1_rx = int(s1_rx1) - int(s1_rx0)
    d1_tx = int(s1_tx1) - int(s1_tx0)
    print(f"Counter Delta Stream 1 (Single Flow): Rx=+{d1_rx/1024/1024:.2f} MB, Tx=+{d1_tx/1024/1024:.2f} MB")

    # 3. UJI PARALEL FLOWS (2 STREAMS DENGAN MULTIPLE PARALLEL CONNECTIONS)
    print("\n--- [C] Uji Multiple Parallel Connections (Stream 1 + Stream 2) ---")
    # Baca counter awal kedua stream
    c1_rx_before = int(run_mini(mini_ssh, "cat /sys/class/net/wgboost1/statistics/rx_bytes")[0])
    c2_rx_before = int(run_mini(mini_ssh, "cat /sys/class/net/wgboost2/statistics/rx_bytes")[0])
    c1_tx_before = int(run_mini(mini_ssh, "cat /sys/class/net/wgboost1/statistics/tx_bytes")[0])
    c2_tx_before = int(run_mini(mini_ssh, "cat /sys/class/net/wgboost2/statistics/tx_bytes")[0])

    # Jalankan 4 download paralel simultan: 2 melalui IP 10.250.1.2, 2 melalui IP 10.250.2.2
    cmd_parallel = """
    (curl -s --interface 10.250.1.2 -o /dev/null http://speed.cloudflare.com/__down?bytes=25000000 & \
     curl -s --interface 10.250.1.2 -o /dev/null http://speed.cloudflare.com/__down?bytes=25000000 & \
     curl -s --interface 10.250.2.2 -o /dev/null http://speed.cloudflare.com/__down?bytes=25000000 & \
     curl -s --interface 10.250.2.2 -o /dev/null http://speed.cloudflare.com/__down?bytes=25000000 & \
     wait)
    """
    t_start = time.time()
    run_mini(mini_ssh, cmd_parallel)
    t_elapsed = time.time() - t_start

    c1_rx_after = int(run_mini(mini_ssh, "cat /sys/class/net/wgboost1/statistics/rx_bytes")[0])
    c2_rx_after = int(run_mini(mini_ssh, "cat /sys/class/net/wgboost2/statistics/rx_bytes")[0])
    c1_tx_after = int(run_mini(mini_ssh, "cat /sys/class/net/wgboost1/statistics/tx_bytes")[0])
    c2_tx_after = int(run_mini(mini_ssh, "cat /sys/class/net/wgboost2/statistics/tx_bytes")[0])

    diff1_rx = c1_rx_after - c1_rx_before
    diff2_rx = c2_rx_after - c2_rx_before
    total_rx = diff1_rx + diff2_rx
    mbps = (total_rx * 8) / (t_elapsed * 1_000_000)

    print(f"Durasi Transfer Paralel: {t_elapsed:.2f} detik")
    print(f"Stream 1 Delta: Rx=+{diff1_rx/1024/1024:.2f} MB, Tx=+{(c1_tx_after-c1_tx_before)/1024/1024:.2f} MB")
    print(f"Stream 2 Delta: Rx=+{diff2_rx/1024/1024:.2f} MB, Tx=+{(c2_tx_after-c2_tx_before)/1024/1024:.2f} MB")
    print(f"Total Agregasi Paralel Rx: {total_rx/1024/1024:.2f} MB | Throughput Agregat: {mbps:.2f} Mbps")

    # Clean up test interfaces
    run_mini(mini_ssh, "wg-quick down wgboost1; wg-quick down wgboost2")

    mini_ssh.close()
    vps_ssh.close()
    print("=====================================================================")

if __name__ == '__main__':
    main()

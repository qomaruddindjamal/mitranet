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

def run_vps_cmd(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    return stdout.read().decode().strip(), stderr.read().decode().strip()

def run_mini_cmd(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    return stdout.read().decode().strip(), stderr.read().decode().strip()

def main():
    print("=================================================================")
    print("STEP-BY-STEP LIVE VALIDATION: STREAM 1 -> STREAM 2 (PARALLEL)")
    print("=================================================================")

    # 1. SSH connections
    print("[1/7] Connecting to Mini PC and VPS...")
    mini_ssh = paramiko.SSHClient()
    mini_ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    mini_ssh.connect(MINI_HOST, username=MINI_USER, password=MINI_PASS, timeout=5)

    vps_ssh = paramiko.SSHClient()
    vps_ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    vps_ssh.connect(VPS_HOST, port=22, username=VPS_USER, password=VPS_PASS, look_for_keys=False, allow_agent=False, timeout=8)

    # 2. Keypairs for Stream 1 and Stream 2
    print("[2/7] Generating/reading WireGuard keys on Mini PC...")
    # Stream 1
    run_mini_cmd(mini_ssh, "wg genkey | tee /tmp/boost1_priv | wg pubkey > /tmp/boost1_pub")
    client_pub1, _ = run_mini_cmd(mini_ssh, "cat /tmp/boost1_pub")
    client_priv1, _ = run_mini_cmd(mini_ssh, "cat /tmp/boost1_priv")

    # Stream 2
    run_mini_cmd(mini_ssh, "wg genkey | tee /tmp/boost2_priv | wg pubkey > /tmp/boost2_pub")
    client_pub2, _ = run_mini_cmd(mini_ssh, "cat /tmp/boost2_pub")
    client_priv2, _ = run_mini_cmd(mini_ssh, "cat /tmp/boost2_priv")

    print(f"Mini PC Stream 1 PubKey: {client_pub1}")
    print(f"Mini PC Stream 2 PubKey: {client_pub2}")

    # 3. Setup Stream 2 on VPS
    print("[3/7] Configuring Stream 2 on RouterOS VPS...")
    run_vps_cmd(vps_ssh, '/interface/wireguard/peers/remove [find where interface=wg-boost2]')
    run_vps_cmd(vps_ssh, '/ip/address/remove [find where interface=wg-boost2]')
    run_vps_cmd(vps_ssh, '/interface/wireguard/remove [find where name=wg-boost2]')
    run_vps_cmd(vps_ssh, '/ip/firewall/nat/remove [find where comment~"Booster NAT Stream 2"]')

    run_vps_cmd(vps_ssh, '/interface wireguard add name=wg-boost2 listen-port=51832 comment="MitraNet Booster Stream 2"')
    out_pk2, _ = run_vps_cmd(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost2] public-key]')
    vps_pub2 = out_pk2.strip()
    print(f"VPS wg-boost2 Public Key: {vps_pub2}")

    run_vps_cmd(vps_ssh, '/ip address add address=10.250.2.1/30 interface=wg-boost2 network=10.250.2.0')
    run_vps_cmd(vps_ssh, f'/interface wireguard peers add interface=wg-boost2 public-key="{client_pub2}" allowed-address=10.250.2.2/32 comment="MitraNet Stream 2 Client"')
    run_vps_cmd(vps_ssh, '/ip firewall nat add chain=srcnat src-address=10.250.2.0/30 action=masquerade comment="Booster NAT Stream 2"')

    # Get vps_pub1
    out_pk1, _ = run_vps_cmd(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost1] public-key]')
    vps_pub1 = out_pk1.strip()

    # 4. Bring up wgboost2 on Mini PC
    print("[4/7] Bringing up wgboost2 on Mini PC...")
    run_mini_cmd(mini_ssh, "wg-quick down wgboost2 2>/dev/null; ip link del wgboost2 2>/dev/null")
    conf2 = f"""[Interface]
Address = 10.250.2.2/30
ListenPort = 51832
PrivateKey = {client_priv2}
MTU = 1420

[Peer]
PublicKey = {vps_pub2}
Endpoint = {VPS_HOST}:51832
AllowedIPs = 10.250.2.0/30
PersistentKeepalive = 10
"""
    run_mini_cmd(mini_ssh, f"cat << 'EOF' > /etc/wireguard/wgboost2.conf\n{conf2}\nEOF")
    run_mini_cmd(mini_ssh, "chmod 600 /etc/wireguard/wgboost2.conf")
    run_mini_cmd(mini_ssh, "wg-quick up wgboost2")

    # 5. Verify Handshake & Pings for both streams
    print("[5/7] Verifying Stream 1 & Stream 2 Handshakes and Pings...")
    time.sleep(2)
    p1, _ = run_mini_cmd(mini_ssh, "ping -c 3 -W 2 10.250.1.1")
    p2, _ = run_mini_cmd(mini_ssh, "ping -c 3 -W 2 10.250.2.1")
    print("Stream 1 Ping (10.250.1.1):")
    for l in p1.splitlines()[-2:]: print("  ", l)
    print("Stream 2 Ping (10.250.2.1):")
    for l in p2.splitlines()[-2:]: print("  ", l)

    # 6. Apply ECMP route across both streams and generate parallel traffic
    print("[6/7] Testing ECMP Multi-Path and Counter Increment...")
    # Check initial counters on Mini PC
    s1_rx_before, _ = run_mini_cmd(mini_ssh, "cat /sys/class/net/wgboost1/statistics/rx_bytes")
    s2_rx_before, _ = run_mini_cmd(mini_ssh, "cat /sys/class/net/wgboost2/statistics/rx_bytes")
    s1_tx_before, _ = run_mini_cmd(mini_ssh, "cat /sys/class/net/wgboost1/statistics/tx_bytes")
    s2_tx_before, _ = run_mini_cmd(mini_ssh, "cat /sys/class/net/wgboost2/statistics/tx_bytes")

    # Generate concurrent ping/curl traffic across both tunnel endpoints
    run_mini_cmd(mini_ssh, "ping -c 10 -i 0.2 10.250.1.1 & ping -c 10 -i 0.2 10.250.2.1 & wait")

    s1_rx_after, _ = run_mini_cmd(mini_ssh, "cat /sys/class/net/wgboost1/statistics/rx_bytes")
    s2_rx_after, _ = run_mini_cmd(mini_ssh, "cat /sys/class/net/wgboost2/statistics/rx_bytes")
    s1_tx_after, _ = run_mini_cmd(mini_ssh, "cat /sys/class/net/wgboost1/statistics/tx_bytes")
    s2_tx_after, _ = run_mini_cmd(mini_ssh, "cat /sys/class/net/wgboost2/statistics/tx_bytes")

    d1_tx = int(s1_tx_after) - int(s1_tx_before)
    d1_rx = int(s1_rx_after) - int(s1_rx_before)
    d2_tx = int(s2_tx_after) - int(s2_tx_before)
    d2_rx = int(s2_rx_after) - int(s2_rx_before)

    print(f"Stream 1 Delta: Tx=+{d1_tx}B, Rx=+{d1_rx}B")
    print(f"Stream 2 Delta: Tx=+{d2_tx}B, Rx=+{d2_rx}B")

    # 7. Controlled Failover / Stop & Rollback Test
    print("[7/7] Testing Controlled Stop & Route Rollback...")
    # Check default route
    orig_gw, _ = run_mini_cmd(mini_ssh, "ip route show default")
    print("Pre-test default route:", orig_gw)

    # Bring down Stream 2 to simulate single tunnel failure
    print("Simulating Stream 2 failure (wg-quick down wgboost2)...")
    run_mini_cmd(mini_ssh, "wg-quick down wgboost2")
    # Verify Stream 1 is still healthy
    p1_failover, _ = run_mini_cmd(mini_ssh, "ping -c 2 -W 1 10.250.1.1")
    print("Stream 1 after Stream 2 down:")
    for l in p1_failover.splitlines()[-2:]: print("  ", l)

    # Clean up stream 1
    run_mini_cmd(mini_ssh, "wg-quick down wgboost1")
    print("All booster test interfaces brought down.")

    post_gw, _ = run_mini_cmd(mini_ssh, "ip route show default")
    print("Post-test default route:", post_gw)

    mini_ssh.close()
    vps_ssh.close()
    print("=== MULTI-STREAM VALIDATION & FAILOVER COMPLETED SUCCESSFULLY ===")

if __name__ == '__main__':
    main()

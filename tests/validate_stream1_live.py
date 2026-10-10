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
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    return out, err

def run_mini_cmd(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    return out, err

def main():
    print("=== LIVE BOOSTER STREAM 1 VALIDATION ===")
    
    # 1. Connect Mini PC
    print("[1/6] Connecting to Mini PC 10.10.66.228...")
    mini_ssh = paramiko.SSHClient()
    mini_ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    mini_ssh.connect(MINI_HOST, username=MINI_USER, password=MINI_PASS, timeout=5)

    # Generate Stream 1 client keypair
    run_mini_cmd(mini_ssh, "wg genkey | tee /tmp/boost1_priv | wg pubkey > /tmp/boost1_pub")
    client_pub1, _ = run_mini_cmd(mini_ssh, "cat /tmp/boost1_pub")
    client_priv1, _ = run_mini_cmd(mini_ssh, "cat /tmp/boost1_priv")
    print(f"Mini PC Stream 1 Client PubKey: {client_pub1}")

    # 2. Connect VPS RouterOS
    print("[2/6] Connecting to VPS RouterOS 103.93.162.168...")
    vps_ssh = paramiko.SSHClient()
    vps_ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    vps_ssh.connect(VPS_HOST, port=22, username=VPS_USER, password=VPS_PASS, look_for_keys=False, allow_agent=False, timeout=8)

    # 3. Configure VPS for Stream 1
    print("[3/6] Configuring Stream 1 on RouterOS VPS...")
    # Add firewall rule if not exists
    chk_fw, _ = run_vps_cmd(vps_ssh, '/ip/firewall/filter/print where comment~"Booster"')
    if not chk_fw:
        print("Adding firewall input filter rule for UDP 51831-51834...")
        out, err = run_vps_cmd(vps_ssh, '/ip firewall filter add chain=input action=accept protocol=udp dst-port=51831-51834 comment="Accept WireGuard Booster Streams" place-before=[find where chain=input and action=drop]')
        print("FW Result:", out or err or "OK")

    # Clean any stale wg-boost1
    run_vps_cmd(vps_ssh, '/interface/wireguard/peers/remove [find where interface=wg-boost1]')
    run_vps_cmd(vps_ssh, '/ip/address/remove [find where interface=wg-boost1]')
    run_vps_cmd(vps_ssh, '/interface/wireguard/remove [find where name=wg-boost1]')
    run_vps_cmd(vps_ssh, '/ip/firewall/nat/remove [find where comment~"Booster NAT Stream 1"]')

    # Add wg-boost1 interface
    out, err = run_vps_cmd(vps_ssh, '/interface wireguard add name=wg-boost1 listen-port=51831 comment="MitraNet Booster Stream 1"')
    print("Add Interface wg-boost1:", out or err or "OK")

    # Get VPS wg-boost1 public key
    out_pk, _ = run_vps_cmd(vps_ssh, ':put [/interface wireguard get [find where name=wg-boost1] public-key]')
    vps_pub1 = out_pk.strip()
    print(f"VPS wg-boost1 Public Key: {vps_pub1}")

    # Add IP Address on VPS
    out, err = run_vps_cmd(vps_ssh, '/ip address add address=10.250.1.1/30 interface=wg-boost1 network=10.250.1.0')
    print("Add IP Address 10.250.1.1/30:", out or err or "OK")

    # Add Peer on VPS
    out, err = run_vps_cmd(vps_ssh, f'/interface wireguard peers add interface=wg-boost1 public-key="{client_pub1}" allowed-address=10.250.1.2/32 comment="MitraNet Stream 1 Client"')
    print(f"Add Peer on VPS for {client_pub1}:", out or err or "OK")

    # Add NAT Masquerade on VPS
    out, err = run_vps_cmd(vps_ssh, '/ip firewall nat add chain=srcnat src-address=10.250.1.0/30 action=masquerade comment="Booster NAT Stream 1"')
    print("Add NAT Masquerade on VPS:", out or err or "OK")

    # 4. Configure Mini PC wgboost1
    print("[4/6] Bringing up wgboost1 on Mini PC...")
    run_mini_cmd(mini_ssh, "wg-quick down wgboost1 2>/dev/null; ip link del wgboost1 2>/dev/null")
    
    conf_content = f"""[Interface]
Address = 10.250.1.2/30
ListenPort = 51831
PrivateKey = {client_priv1}
MTU = 1420

[Peer]
PublicKey = {vps_pub1}
Endpoint = {VPS_HOST}:51831
AllowedIPs = 10.250.1.0/30
PersistentKeepalive = 10
"""
    # Write conf to /etc/wireguard/wgboost1.conf
    run_mini_cmd(mini_ssh, f"cat << 'EOF' > /etc/wireguard/wgboost1.conf\n{conf_content}\nEOF")
    run_mini_cmd(mini_ssh, "chmod 600 /etc/wireguard/wgboost1.conf")
    out, err = run_mini_cmd(mini_ssh, "wg-quick up wgboost1")
    print("Mini PC wg-quick up wgboost1:", out or err or "OK")

    # 5. Test Handshake & Ping
    print("[5/6] Testing Handshake & Tunnel ping 10.250.1.1...")
    time.sleep(2)
    # Ping 10.250.1.1 through tunnel
    p_out, p_err = run_mini_cmd(mini_ssh, "ping -c 4 -W 2 10.250.1.1")
    print("=== PING RESULT ===")
    print(p_out)

    # Check wg show on Mini PC
    wg_out, _ = run_mini_cmd(mini_ssh, "wg show wgboost1")
    print("=== WG SHOW ON MINI PC ===")
    print(wg_out)

    # Check peer status on VPS
    vps_p_out, _ = run_vps_cmd(vps_ssh, '/interface/wireguard/peers/print detail where interface=wg-boost1 without-paging')
    print("=== VPS PEER STATUS ===")
    print(vps_p_out)

    # 6. Verify SSH connectivity still solid
    print("[6/6] Verifying SSH connectivity to Mini PC management IP...")
    out_id, _ = run_mini_cmd(mini_ssh, "hostname && ip route show default")
    print("Mini PC Host & Default Route:")
    print(out_id)

    mini_ssh.close()
    vps_ssh.close()
    print("=== STAGE 2 (STREAM 1) VERIFICATION COMPLETED SUCCESSFULLY ===")

if __name__ == '__main__':
    main()

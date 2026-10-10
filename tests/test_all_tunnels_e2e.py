import json
import time
import paramiko

# 1. Connect to VPS MikroTik CHR
vps = paramiko.SSHClient()
vps.set_missing_host_key_policy(paramiko.AutoAddPolicy())
vps.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)

# 2. Connect to MitraNet Mini PC
mitra = paramiko.SSHClient()
mitra.set_missing_host_key_policy(paramiko.AutoAddPolicy())
mitra.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

results = {}

print("==================================================")
print("1. SETUP FIREWALL PADA MIKROTIK VPS")
print("==================================================")
vps_fw_cmds = [
    '/ip firewall filter remove [find comment="Allow-IPIP"]',
    '/ip firewall filter remove [find comment="Allow-VXLAN"]',
    '/ip firewall filter add chain=input protocol=ipencap action=accept place-before=20 comment="Allow-IPIP"',
    '/ip firewall filter add chain=input protocol=udp dst-port=4789 action=accept place-before=21 comment="Allow-VXLAN"',
]
for cmd in vps_fw_cmds:
    _, stdout, _ = vps.exec_command(cmd)
    stdout.read()

print("Firewall updated on VPS!")

# ==================================================
# 2. TEST IPIP (IP-over-IP, Layer 3)
# ==================================================
print("\n==================================================")
print("2. UJI COBA IPIP TUNNEL (Layer 3)")
print("==================================================")
# Configure VPS
vps_ipip = [
    '/interface ipip remove [find name=ipip-mitranet]',
    '/ip address remove [find interface=ipip-mitranet]',
    '/interface ipip add name=ipip-mitranet local-address=10.250.1.1 remote-address=10.250.1.2 comment=Stage2-IPIP',
    '/ip address add address=10.253.1.1/30 interface=ipip-mitranet'
]
for cmd in vps_ipip:
    _, stdout, _ = vps.exec_command(cmd)
    stdout.read()

# Configure MitraNet via API
ipip_payload = {
    "type": "ipip",
    "name": "ipip-vps",
    "local": "10.250.1.2",
    "remote": "10.250.1.1",
    "ttl": 64,
    "mtu": 1480,
    "ip_cidr": "10.253.1.2/30"
}
cmd = f"curl -s -X POST http://127.0.0.1:8443/api/v1/interfaces/tunnel/create -H 'Content-Type: application/json' -d '{json.dumps(ipip_payload)}'"
_, stdout, _ = mitra.exec_command(cmd)
print("MitraNet IPIP API Response:", stdout.read().decode('utf-8'))

time.sleep(2)
# Ping MitraNet -> VPS
_, stdout, _ = mitra.exec_command("ping -c 4 10.253.1.1")
out_ping_mitra = stdout.read().decode('utf-8')
print("Ping MitraNet -> VPS (IPIP):\n", out_ping_mitra)

# Ping VPS -> MitraNet
_, stdout, _ = vps.exec_command("/ping count=4 10.253.1.2")
out_ping_vps = stdout.read().decode('utf-8')
print("Ping VPS -> MitraNet (IPIP):\n", out_ping_vps)

results['IPIP'] = {
    'mitra_to_vps': '0% packet loss' in out_ping_mitra,
    'vps_to_mitra': 'packet-loss=0%' in out_ping_vps
}

# ==================================================
# 3. TEST EOIP (Ethernet-over-IP, Layer 2)
# ==================================================
print("\n==================================================")
print("3. UJI COBA EOIP TUNNEL (Layer 2 Ethernet)")
print("==================================================")
# Configure VPS
vps_eoip = [
    '/interface eoip remove [find name=eoip-mitranet]',
    '/ip address remove [find interface=eoip-mitranet]',
    '/interface eoip add name=eoip-mitranet local-address=10.250.1.1 remote-address=10.250.1.2 tunnel-id=77 comment=Stage2-EoIP',
    '/ip address add address=10.252.1.1/30 interface=eoip-mitranet'
]
for cmd in vps_eoip:
    _, stdout, _ = vps.exec_command(cmd)
    stdout.read()

# Configure MitraNet via API
eoip_payload = {
    "type": "eoip",
    "name": "eoip-vps",
    "local": "10.250.1.2",
    "remote": "10.250.1.1",
    "tunnel_id": 77,
    "mtu": 1500,
    "ip_cidr": "10.252.1.2/30"
}
cmd = f"curl -s -X POST http://127.0.0.1:8443/api/v1/interfaces/tunnel/create -H 'Content-Type: application/json' -d '{json.dumps(eoip_payload)}'"
_, stdout, _ = mitra.exec_command(cmd)
print("MitraNet EoIP API Response:", stdout.read().decode('utf-8'))

time.sleep(2)
# Ping MitraNet -> VPS
_, stdout, _ = mitra.exec_command("ping -c 4 10.252.1.1")
out_ping_mitra_eoip = stdout.read().decode('utf-8')
print("Ping MitraNet -> VPS (EoIP):\n", out_ping_mitra_eoip)

# Ping VPS -> MitraNet
_, stdout, _ = vps.exec_command("/ping count=4 10.252.1.2")
out_ping_vps_eoip = stdout.read().decode('utf-8')
print("Ping VPS -> MitraNet (EoIP):\n", out_ping_vps_eoip)

results['EoIP'] = {
    'mitra_to_vps': '0% packet loss' in out_ping_mitra_eoip,
    'vps_to_mitra': 'packet-loss=0%' in out_ping_vps_eoip
}

# ==================================================
# 4. TEST VXLAN (Virtual Extensible LAN, Layer 2 Overlay)
# ==================================================
print("\n==================================================")
print("4. UJI COBA VXLAN (Layer 2 Overlay)")
print("==================================================")
# Configure VPS
vps_vxlan = [
    '/interface vxlan vteps remove [find interface=vxlan-mitranet]',
    '/interface vxlan remove [find name=vxlan-mitranet]',
    '/ip address remove [find interface=vxlan-mitranet]',
    '/interface vxlan add name=vxlan-mitranet vni=100 port=4789 comment=Stage2-VXLAN',
    '/interface vxlan vteps add interface=vxlan-mitranet remote-ip=10.250.1.2 port=4789',
    '/ip address add address=10.251.1.1/30 interface=vxlan-mitranet'
]
for cmd in vps_vxlan:
    _, stdout, _ = vps.exec_command(cmd)
    stdout.read()

# Configure MitraNet via API
vxlan_payload = {
    "type": "vxlan",
    "name": "vxlan100",
    "vni": 100,
    "port": 4789,
    "remote": "10.250.1.1",
    "parent": "wgboost1",
    "ip_cidr": "10.251.1.2/30"
}
cmd = f"curl -s -X POST http://127.0.0.1:8443/api/v1/interfaces/tunnel/create -H 'Content-Type: application/json' -d '{json.dumps(vxlan_payload)}'"
_, stdout, _ = mitra.exec_command(cmd)
print("MitraNet VXLAN API Response:", stdout.read().decode('utf-8'))

time.sleep(2)
# Ping MitraNet -> VPS
_, stdout, _ = mitra.exec_command("ping -c 4 10.251.1.1")
out_ping_mitra_vxlan = stdout.read().decode('utf-8')
print("Ping MitraNet -> VPS (VXLAN):\n", out_ping_mitra_vxlan)

# Ping VPS -> MitraNet
_, stdout, _ = vps.exec_command("/ping count=4 10.251.1.2")
out_ping_vps_vxlan = stdout.read().decode('utf-8')
print("Ping VPS -> MitraNet (VXLAN):\n", out_ping_vps_vxlan)

results['VXLAN'] = {
    'mitra_to_vps': '0% packet loss' in out_ping_mitra_vxlan,
    'vps_to_mitra': 'packet-loss=0%' in out_ping_vps_vxlan
}

# ==================================================
# 5. TEST GRE (sudah teruji sebelumnya, validasi ulang)
# ==================================================
_, stdout, _ = mitra.exec_command("ping -c 4 10.254.1.1")
out_ping_gre = stdout.read().decode('utf-8')
results['GRE'] = {
    'mitra_to_vps': '0% packet loss' in out_ping_gre
}

print("\n==================================================")
print("SUMMARY RESULTS:", json.dumps(results, indent=2))
print("==================================================")

vps.close()
mitra.close()

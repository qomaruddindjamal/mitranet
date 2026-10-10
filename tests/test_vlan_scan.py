import paramiko
import time

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

sftp = client.open_sftp()
with sftp.file('/tmp/test_vlan_scan.py', 'w') as f:
    f.write('''
import subprocess
import time
import re

candidates = [1, 2, 10, 20, 50, 100, 200]
results = {}

print("=== STARTING VLAN HOPPING PROBE ON enp1s0 ===")
for vlan_id in candidates:
    ifname = f"vlan{vlan_id}"
    print(f"\\n[*] Testing VLAN ID {vlan_id} ({ifname})...")
    # Clean previous
    subprocess.run(["ip", "link", "del", ifname], stderr=subprocess.DEVNULL)
    # Add vlan subinterface
    cmd_add = ["ip", "link", "add", "link", "enp1s0", "name", ifname, "type", "vlan", "id", str(vlan_id)]
    res = subprocess.run(cmd_add, capture_output=True, text=True)
    if res.returncode != 0:
        print(f"[-] Failed to create {ifname}: {res.stderr.strip()}")
        continue
    
    subprocess.run(["ip", "link", "set", ifname, "up"])
    time.sleep(1)
    
    # Run dhcpcd probe with timeout 6s
    dhcp_proc = subprocess.run(["dhcpcd", "-4", "-t", "6", "-n", ifname], capture_output=True, text=True)
    out = dhcp_proc.stdout + dhcp_proc.stderr
    
    # Check if we got an IP
    ip_proc = subprocess.run(["ip", "-4", "-br", "addr", "show", "dev", ifname], capture_output=True, text=True)
    ip_line = ip_proc.stdout.strip()
    
    print(f"    dhcpcd output: {out.strip()[:100]}")
    print(f"    IP status: {ip_line}")
    
    match = re.search(r'([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/[0-9]+)', ip_line)
    if match:
        assigned_ip = match.group(1)
        print(f"[+] SUCCESS! VLAN {vlan_id} obtained IP: {assigned_ip}")
        results[vlan_id] = assigned_ip
    else:
        print(f"[-] No DHCP response on VLAN {vlan_id}")
        # cleanup
        subprocess.run(["ip", "link", "del", ifname], stderr=subprocess.DEVNULL)

print("\\n=== SCAN SUMMARY ===")
print("Active VLANs discovered:", results)
''')
sftp.close()

stdin, stdout, stderr = client.exec_command('python3 /tmp/test_vlan_scan.py')
print(stdout.read().decode())
client.close()

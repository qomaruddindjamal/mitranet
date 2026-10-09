import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('192.168.56.101', port=22, username='root', password='mitranet')

commands = [
    ("DHCP / DNS Services", "ps aux | grep -E 'dnsmasq|dhcp|kea' | grep -v grep"),
    ("veth0 status & IP", "ip addr show veth0"),
    ("IP Forwarding", "cat /proc/sys/net/ipv4/ip_forward"),
    ("vEthernet Config", "cat /etc/mitranet/network/vethernet.json"),
    ("System DHCP server installed?", "dpkg -l | grep -E 'dnsmasq|isc-dhcp|kea'"),
    ("Listening UDP 67 (DHCP Server)", "ss -unlp | grep :67"),
]

for title, cmd in commands:
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    print(f"=== {title} ===")
    if out:
        print(out)
    if err:
        print("ERR:", err)
    print()

client.close()

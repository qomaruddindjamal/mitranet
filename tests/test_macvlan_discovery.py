import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

sftp = client.open_sftp()
with sftp.file('/tmp/disco.py', 'w') as f:
    f.write('''
from mitranet.core.network.discovery import InterfaceDiscoveryService
svc = InterfaceDiscoveryService()
ifaces = svc.discover_interfaces()
print(f"Total discovered: {len(ifaces)}")
for i in ifaces:
    if "mac" in i.name.lower() or "mac" in i.type.lower():
        print(f"FOUND: name={i.name}, type={i.type}, parent={i.parent_device}, mac={i.mac_address}")
''')
sftp.close()

stdin, stdout, stderr = client.exec_command('python3 /tmp/disco.py')
print("STDOUT:\n", stdout.read().decode())
print("STDERR:\n", stderr.read().decode())
client.close()

import paramiko

c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect('192.168.56.101', username='root', password='mitranet')

cmd = """python3 -c "
import json
from mitranet.core.network.discovery import InterfaceDiscoveryService
s = InterfaceDiscoveryService()
ifaces = s.discover_interfaces()
for i in ifaces:
    print(i.name, i.type, i.altname, i.port_label)
" """

stdin, stdout, stderr = c.exec_command(cmd)
print("STDOUT:\n", stdout.read().decode())
print("STDERR:\n", stderr.read().decode())
c.close()

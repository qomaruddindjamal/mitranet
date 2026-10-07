import os
import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('192.168.56.101', port=22, username='root', password='mitranet')
sftp = client.open_sftp()

local_server = r'C:\mitranet\src\api\server.py'
remote_dist = '/usr/lib/python3/dist-packages/mitranet/src/api/server.py'
remote_src = '/mitranet/src/api/server.py'

print(f"Deploying {local_server} to {remote_dist} and {remote_src}...")
sftp.put(local_server, remote_dist)
sftp.put(local_server, remote_src)
sftp.close()

stdin, stdout, stderr = client.exec_command("systemctl restart mitranet-webui && systemctl is-active mitranet-webui")
print("mitranet-webui status:", stdout.read().decode('utf-8', errors='ignore').strip())
client.close()

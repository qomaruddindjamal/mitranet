import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('192.168.56.101', port=22, username='root', password='mitranet')

cmd = "python3 -c \"import mitranet.src.api.server as s; print(s.__file__)\""
stdin, stdout, stderr = client.exec_command(cmd)
print("Loaded server.py:", stdout.read().decode('utf-8', errors='ignore').strip())

client.close()

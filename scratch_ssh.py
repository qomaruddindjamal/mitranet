import paramiko
import sys

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

cmd = sys.argv[1] if len(sys.argv) > 1 else 'echo "Connected to Mini PC"'
stdin, stdout, stderr = client.exec_command(cmd)
out = stdout.read().decode()
err = stderr.read().decode()
print("STDOUT:\n" + out)
if err:
    print("STDERR:\n" + err)
client.close()

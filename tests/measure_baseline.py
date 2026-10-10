import paramiko
import time

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

cmds = [
    'ip route show',
    'wg show',
    'curl -s -w "\\nTime: %{time_total}s | Speed: %{speed_download} bytes/s | HTTP: %{http_code}\\n" -o /dev/null http://speed.cloudflare.com/__down?bytes=10000000',
    'ping -c 4 -W 2 103.93.162.168'
]

for cmd in cmds:
    print(f"=== CMD: {cmd} ===")
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode())

ssh.close()

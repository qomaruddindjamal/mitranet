import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=10)

commands = [
    "ip addr show wg0",
    "ping -c 5 10.10.77.1",
    "curl --interface 10.10.77.3 -s https://ifconfig.me",
    "curl --interface 10.10.77.3 -o /dev/null -s -w 'Speed: %{speed_download} B/s, Time: %{time_total}s\n' https://speed.cloudflare.com/__down?bytes=25000000",
    "curl --interface 10.10.77.3 -o /dev/null -s -w 'Speed: %{speed_download} B/s, Time: %{time_total}s\n' https://speed.cloudflare.com/__down?bytes=50000000",
    "/usr/local/bin/speedtest-cli --simple"
]

for cmd in commands:
    print(f"=== RUNNING: {cmd} ===")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out:
        print(f"STDOUT:\n{out}")
    if err:
        print(f"STDERR:\n{err}")

ssh.close()

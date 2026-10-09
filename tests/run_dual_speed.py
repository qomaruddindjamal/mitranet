import paramiko
import time

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.209', username='root', password='mitranet', timeout=5)

# 1. Test Single IP 10.10.66.208
print("=== PENGUJIAN 1: IP UTAMA (10.10.66.208) ===")
cmd1 = "curl --interface 10.10.66.208 -o /dev/null -s -w 'Speed: %{speed_download} Bytes/s, Time: %{time_total}s\n' https://speed.cloudflare.com/__down?bytes=5000000"
_, out1, _ = ssh.exec_command(cmd1)
res1 = out1.read().decode().strip()
print(res1)

# 2. Test Single Virtual IP 10.10.66.209
print("\n=== PENGUJIAN 2: IP VIRTUAL MACVLAN (10.10.66.209) ===")
cmd2 = "curl --interface 10.10.66.209 -o /dev/null -s -w 'Speed: %{speed_download} Bytes/s, Time: %{time_total}s\n' https://speed.cloudflare.com/__down?bytes=5000000"
_, out2, _ = ssh.exec_command(cmd2)
res2 = out2.read().decode().strip()
print(res2)

# 3. Test Parallel Concurrent Download (Keduanya mendownload secara bersamaan!)
print("\n=== PENGUJIAN 3: SIMULTAN BERSAMAAN (DUAL IP PARALEL) ===")
cmd_concurrent = "(curl --interface 10.10.66.208 -o /dev/null -s -w 'Stream 1 (208): %{speed_download} B/s in %{time_total}s\n' https://speed.cloudflare.com/__down?bytes=5000000 & curl --interface 10.10.66.209 -o /dev/null -s -w 'Stream 2 (209): %{speed_download} B/s in %{time_total}s\n' https://speed.cloudflare.com/__down?bytes=5000000 & wait)"
_, out3, _ = ssh.exec_command(cmd_concurrent)
print(out3.read().decode().strip())

ssh.close()

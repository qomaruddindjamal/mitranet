import paramiko

passwords = ['K0323205', 'mitranet', 'admin', 'citramedia', '']
users = ['admin', 'citramedia', 'root']

print("=== TESTING ACCESS TO MIKROTIK UTAMA (10.10.66.1) ===")
connected = False
for u in users:
    for p in passwords:
        try:
            ssh = paramiko.SSHClient()
            ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            ssh.connect('10.10.66.1', port=22, username=u, password=p, timeout=2)
            print(f"[+] SUCCESS! Connected to 10.10.66.1 as user='{u}', pass='{p}'")
            stdin, stdout, stderr = ssh.exec_command('/system/identity/print; /queue/simple/print')
            print(stdout.read().decode())
            ssh.close()
            connected = True
            break
        except Exception as e:
            pass
    if connected:
        break

if not connected:
    print("[-] Could not login to 10.10.66.1 with test credentials.")

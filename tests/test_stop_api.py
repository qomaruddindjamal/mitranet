import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

cmd = "curl -s -i -k -X POST -H 'Content-Type: application/json' -d '{\"role\":\"client\"}' http://127.0.0.1:8443/api/v1/vpn/booster/stop"
stdin, stdout, stderr = ssh.exec_command(cmd)
print("=== POST STOP LOCALHOST ===")
print(stdout.read().decode())

cmd2 = "curl -s -i -k -X POST -H 'Content-Type: application/json' -d '{\"role\":\"client\"}' http://10.10.66.228:8443/api/v1/vpn/booster/stop"
stdin, stdout, stderr = ssh.exec_command(cmd2)
print("=== POST STOP NON-LOOPBACK ===")
print(stdout.read().decode())

ssh.close()

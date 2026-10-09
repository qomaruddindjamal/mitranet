import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', username='root', password='mitranet')

# Test direct curl to 127.0.0.1:8000
cmd = """curl -i -X POST http://127.0.0.1:8000/services_virtual.php -d "act=stop&id=aapanel" """
stdin, stdout, stderr = ssh.exec_command(cmd)
print("=== CURL TO PHP PORT 8000 ===")
print("OUT:", stdout.read().decode('utf-8', errors='ignore'))
print("ERR:", stderr.read().decode('utf-8', errors='ignore'))

# Check php error log
stdin, stdout, stderr = ssh.exec_command("php -i | grep error_log")
print("=== PHP ERROR LOG CONFIG ===")
print(stdout.read().decode('utf-8', errors='ignore'))

ssh.close()

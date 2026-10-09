import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', username='root', password='mitranet')

cmd = """php -d display_errors=1 -d error_reporting=E_ALL -r '
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST["act"] = "stop";
$_POST["id"] = "aapanel";
require "/mitranet/web/services_virtual.php";
'"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print("=== PHP CLI RUN OF services_virtual.php ===")
print("OUT:", stdout.read().decode('utf-8', errors='ignore'))
print("ERR:", stderr.read().decode('utf-8', errors='ignore'))
ssh.close()

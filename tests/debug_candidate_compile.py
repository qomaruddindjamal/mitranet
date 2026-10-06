import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', username='root', password='mitranet', timeout=5)

cmd = """python3 -c "
import json
from mitranet.core.firewall.models import FirewallTableConfig
from mitranet.core.firewall.compiler import NftablesCompiler

with open('/var/lib/mitranet/firewall/candidate.json') as f:
    cfg = FirewallTableConfig(**json.load(f))
print(NftablesCompiler.compile_table(cfg))
"
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print('OUTPUT:\n', stdout.read().decode())
print('ERROR:\n', stderr.read().decode())
ssh.close()

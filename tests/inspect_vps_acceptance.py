import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)

cmds = [
    '/ip firewall filter print without-paging',
    '/interface wireguard print detail without-paging',
    '/interface wireguard peers print detail without-paging',
    '/ip address print detail without-paging',
    '/ip firewall nat print without-paging'
]

for cmd in cmds:
    print(f"=== CMD: {cmd} ===")
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode())

ssh.close()

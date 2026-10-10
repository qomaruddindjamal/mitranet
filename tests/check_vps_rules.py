import paramiko
import sys

def check_rules():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)
    stdin, stdout, stderr = ssh.exec_command('/ip/firewall/filter/print where chain="input"')
    print(stdout.read().decode())
    ssh.close()

if __name__ == '__main__':
    check_rules()

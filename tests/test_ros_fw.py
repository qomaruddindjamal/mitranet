import paramiko
import sys

def test_add_fw():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)
    
    # Check if rule exists
    stdin, stdout, stderr = ssh.exec_command('/ip/firewall/filter/print where comment="MitraNetBooster"')
    existing = stdout.read().decode()
    if "MitraNetBooster" not in existing:
        cmd = '/ip/firewall/filter/add chain=input action=accept protocol=udp dst-port=51831-51834 comment="MitraNetBooster" place-before=[:pick [/ip/firewall/filter/find where comment~"Drop all"] 0]'
        stdin, stdout, stderr = ssh.exec_command(cmd)
        print("ADD OUT:", stdout.read().decode())
        print("ADD ERR:", stderr.read().decode())
        
    stdin, stdout, stderr = ssh.exec_command('/ip/firewall/filter/print where comment="MitraNetBooster"')
    print("CURRENT RULE:")
    print(stdout.read().decode())
    ssh.close()

if __name__ == '__main__':
    test_add_fw()

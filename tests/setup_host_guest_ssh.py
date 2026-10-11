
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER, VM_PASS

import paramiko

def check():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect(LAB_HOST, username=LAB_USER, password=LAB_PASS)
    
    # Check ssh keys, install sshpass or set up ssh key on guest
    cmd = """
which sshpass || apt-get install -y sshpass
"""
    stdin, stdout, stderr = c.exec_command(cmd)
    print("INSTALL SSHPASS:\n" + stdout.read().decode())
    
    # Also check if host has an ssh key
    stdin, stdout, stderr = c.exec_command("[ -f /root/.ssh/id_rsa.pub ] || ssh-keygen -t rsa -N '' -f /root/.ssh/id_rsa")
    print("KEYGEN:\n" + stdout.read().decode())

    # Copy key to guest
    stdin, stdout, stderr = c.exec_command(f"sshpass -p '{VM_PASS}' ssh-copy-id -o StrictHostKeyChecking=no root@192.168.101.118")
    print("SSH COPY ID:\n" + stdout.read().decode() + "\n" + stderr.read().decode())

    # Now test direct ssh
    stdin, stdout, stderr = c.exec_command("ssh -o StrictHostKeyChecking=no root@192.168.101.118 'cat /etc/init.d/bt default'")
    print("BT DEFAULT OUTPUT:\n" + stdout.read().decode())

    c.close()

if __name__ == '__main__':
    check()

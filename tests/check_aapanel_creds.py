
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
    
    # Test reading guest aaPanel credentials from host VM
    cmd = f"""
sshpass -p '{VM_PASS}' ssh -o StrictHostKeyChecking=no -o ConnectTimeout=5 root@192.168.101.118 "
cat /www/server/panel/default.pl; echo '===';
cat /www/server/panel/data/admin_path.pl; echo '===';
cat /www/server/panel/data/port.pl; echo '===';
sqlite3 /www/server/panel/data/default.db 'SELECT username FROM users LIMIT 1;'
"
"""
    stdin, stdout, stderr = c.exec_command(cmd)
    out = stdout.read().decode()
    err = stderr.read().decode()
    print("OUTPUT:\n" + out)
    print("ERR:\n" + err)
    c.close()

if __name__ == '__main__':
    check()

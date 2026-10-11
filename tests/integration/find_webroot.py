
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER

import paramiko

def check_nginx():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect(LAB_HOST, username=LAB_USER, password=LAB_PASS)
    
    stdin, stdout, stderr = c.exec_command("grep -rn 'root ' /etc/nginx/ /etc/lighttpd/ /etc/apache2/ 2>/dev/null")
    print("ROOT_SEARCH:\n", stdout.read().decode())
    
    stdin, stdout, stderr = c.exec_command("find / -name 'services_virtual.php' 2>/dev/null")
    print("SERVICES_VIRTUAL_FILES:\n", stdout.read().decode())
    
    c.close()

if __name__ == '__main__':
    check_nginx()

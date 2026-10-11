
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER

import paramiko

def sync_and_test():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect(LAB_HOST, username=LAB_USER, password=LAB_PASS)
    
    sftp = c.open_sftp()
    
    # 1. Upload files to both /mitranet/web and /usr/share/mitranet/web
    files = [
        ('c:/mitranet/src/api/server.py', '/usr/lib/python3/dist-packages/mitranet/src/api/server.py'),
        ('c:/mitranet/web/services_kvm.php', '/mitranet/web/services_kvm.php'),
        ('c:/mitranet/web/services_virtual.php', '/mitranet/web/services_virtual.php'),
        ('c:/mitranet/web/includes/head.inc', '/mitranet/web/includes/head.inc'),
        ('c:/mitranet/web/includes/api.inc', '/mitranet/web/includes/api.inc'),
        
        ('c:/mitranet/web/services_kvm.php', '/usr/share/mitranet/web/services_kvm.php'),
        ('c:/mitranet/web/services_virtual.php', '/usr/share/mitranet/web/services_virtual.php'),
        ('c:/mitranet/web/includes/head.inc', '/usr/share/mitranet/web/includes/head.inc'),
        ('c:/mitranet/web/includes/api.inc', '/usr/share/mitranet/web/includes/api.inc')
    ]
    
    for src, dst in files:
        sftp.put(src, dst)
        print(f"Uploaded {src} -> {dst}")
        
    sftp.close()
    
    # Restart webui service
    stdin, stdout, stderr = c.exec_command("systemctl restart mitranet-webui")
    print("Restarting mitranet-webui...")
    stdout.channel.recv_exit_status()
    
    # Check status
    stdin, stdout, stderr = c.exec_command("systemctl is-active mitranet-webui")
    print("mitranet-webui status:", stdout.read().decode().strip())
    
    c.close()

if __name__ == '__main__':
    sync_and_test()

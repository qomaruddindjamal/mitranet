
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER

import os
import sys
import paramiko

def deploy():
    print("Connecting to MitraNet VM at 192.168.56.101...")
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(LAB_HOST, port=22, username=LAB_USER, password=LAB_PASS, timeout=10)
    sftp = client.open_sftp()

    local_web = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'web'))
    uploaded_count = 0

    for root, dirs, files in os.walk(local_web):
        rel_path = os.path.relpath(root, local_web)
        if rel_path == '.':
            remote_dir = '/mitranet/web'
        else:
            remote_dir = '/mitranet/web/' + rel_path.replace('\\', '/')

        try:
            sftp.mkdir(remote_dir)
        except Exception:
            pass

        for f in files:
            l_file = os.path.join(root, f)
            r_file = remote_dir + '/' + f
            sftp.put(l_file, r_file)
            uploaded_count += 1

    sftp.close()
    print(f"Successfully uploaded {uploaded_count} web files to VM.")

    stdin, stdout, stderr = client.exec_command('systemctl restart mitranet-webui && systemctl is-active mitranet-webui')
    status = stdout.read().decode().strip()
    print(f"mitranet-webui service status: {status}")

    client.close()

if __name__ == '__main__':
    deploy()

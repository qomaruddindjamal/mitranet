
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER

import paramiko

def deploy_100g_and_wifi():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect(LAB_HOST, username=LAB_USER, password=LAB_PASS)
    sftp = c.open_sftp()

    # 1. Upload sysctl 100G
    sftp.put(
        'c:/mitranet/packages/debian/mitranet-core/etc/sysctl.d/99-mitranet-100g-network.conf',
        '/etc/sysctl.d/99-mitranet-100g-network.conf'
    )
    print("Uploaded 99-mitranet-100g-network.conf")

    # 2. Upload modprobe blacklist
    sftp.put(
        'c:/mitranet/packages/debian/mitranet-core/etc/modprobe.d/mitranet-headless-blacklist.conf',
        '/etc/modprobe.d/mitranet-headless-blacklist.conf'
    )
    print("Uploaded mitranet-headless-blacklist.conf")

    # 3. Upload interfaces_assign.php to both locations
    sftp.put('c:/mitranet/web/interfaces_assign.php', '/mitranet/web/interfaces_assign.php')
    sftp.put('c:/mitranet/web/interfaces_assign.php', '/usr/share/mitranet/web/interfaces_assign.php')
    print("Uploaded interfaces_assign.php")

    # 4. Upload updated discovery.py
    sftp.put(
        'c:/mitranet/core/network/discovery.py',
        '/usr/lib/python3/dist-packages/mitranet/core/network/discovery.py'
    )
    print("Uploaded discovery.py")

    sftp.close()

    # Apply sysctl now
    stdin, stdout, stderr = c.exec_command("sysctl --system")
    print("Applied sysctl --system")

    # Restart webui
    stdin, stdout, stderr = c.exec_command("systemctl restart mitranet-webui")
    stdout.channel.recv_exit_status()
    print("Restarted mitranet-webui")

    # Verify key parameters in live kernel
    stdin, stdout, stderr = c.exec_command("sysctl net.core.netdev_max_backlog net.core.rmem_max net.ipv4.tcp_congestion_control net.netfilter.nf_conntrack_max vm.swappiness")
    print("VERIFIED LIVE SYSCTL:\n", stdout.read().decode())

    c.close()

if __name__ == '__main__':
    deploy_100g_and_wifi()

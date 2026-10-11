
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER

import paramiko

def check_10g_caps():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect(LAB_HOST, username=LAB_USER, password=LAB_PASS)

    cmds = [
        ("Installed NIC Tools", "which ethtool ip tc"),
        ("Installed Firmware", "dpkg -l | grep -i firmware"),
        ("TCP Congestion Available", "sysctl net.ipv4.tcp_available_congestion_control"),
        ("Current Core Backlog & TCP Buffers", "sysctl net.core.netdev_max_backlog net.core.rmem_max net.core.wmem_max net.ipv4.tcp_rmem net.ipv4.tcp_wmem"),
        ("Conntrack Limits", "sysctl net.netfilter.nf_conntrack_max 2>/dev/null"),
        ("Default txqueuelen on interface", "ip link | grep -E 'qlen'")
    ]

    for title, cmd in cmds:
        stdin, stdout, stderr = c.exec_command(cmd)
        print(f"=== {title} ===\n{stdout.read().decode().strip()}\n")

    c.close()

if __name__ == '__main__':
    check_10g_caps()

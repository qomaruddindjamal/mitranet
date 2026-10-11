
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER

import paramiko

def calculate_savings():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect(LAB_HOST, username=LAB_USER, password=LAB_PASS)

    cmds = [
        ("RAM Free & Cached", "free -m"),
        ("Slab Kernel (Modul & Driver RAM)", "grep -E 'Slab|SUnreclaim|KernelStack' /proc/meminfo"),
        ("Apt Cache Disk", "du -sm /var/cache/apt /var/lib/apt/lists 2>/dev/null"),
        ("Journal Logs Disk", "journalctl --disk-usage"),
        ("Log Directory Disk", "du -sm /var/log 2>/dev/null"),
        ("Doc and Man pages Disk", "du -sm /usr/share/doc /usr/share/man 2>/dev/null"),
        ("Kernel Modules Disk Size", "du -sm /lib/modules/$(uname -r) 2>/dev/null")
    ]

    for title, cmd in cmds:
        stdin, stdout, stderr = c.exec_command(cmd)
        print(f"=== {title} ===\n{stdout.read().decode().strip()}\n")

    c.close()

if __name__ == '__main__':
    calculate_savings()

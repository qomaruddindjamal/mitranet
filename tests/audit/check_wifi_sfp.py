
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER

import paramiko

def check_wifi_sfp():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect(LAB_HOST, username=LAB_USER, password=LAB_PASS)

    cmds = [
        ("Tools iw/wireless", "which iw iwconfig ethtool 2>/dev/null"),
        ("Installed wifi/sfp packages", "dpkg -l | grep -E 'iw |wpasupplicant|wireless-tools' 2>/dev/null"),
        ("SFP Kernel Modules", "modinfo ixgbe sfp i2c_algo_bit 2>/dev/null | grep -E 'filename|description' | head -n 10"),
        ("Network Drivers Available", "ls -l /lib/modules/$(uname -r)/kernel/drivers/net/ethernet/intel/ 2>/dev/null")
    ]

    for title, cmd in cmds:
        stdin, stdout, stderr = c.exec_command(cmd)
        print(f"=== {title} ===\n{stdout.read().decode().strip()}\n")

    c.close()

if __name__ == '__main__':
    check_wifi_sfp()

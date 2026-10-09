import paramiko

def check_wifi_sfp():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')

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

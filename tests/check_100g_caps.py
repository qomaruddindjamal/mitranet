import paramiko

def check_100g_caps():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')

    cmds = [
        ("Mellanox ConnectX 40G/100G Driver (mlx5_core)", "modinfo mlx5_core 2>/dev/null | grep -E 'filename|description|version' | head -n 4"),
        ("Intel E810 100G Driver (ice)", "modinfo ice 2>/dev/null | grep -E 'filename|description|version' | head -n 4"),
        ("Broadcom 100G Driver (bnxt_en)", "modinfo bnxt_en 2>/dev/null | grep -E 'filename|description|version' | head -n 4"),
        ("Kernel Hugepages (DPDK/High-Speed Memory)", "grep -i huge /proc/meminfo"),
        ("IRQBalance & RPS Support", "which irqbalance; cat /proc/sys/net/core/busy_poll /proc/sys/net/core/busy_read 2>/dev/null")
    ]

    for title, cmd in cmds:
        stdin, stdout, stderr = c.exec_command(cmd)
        print(f"=== {title} ===\n{stdout.read().decode().strip()}\n")

    c.close()

if __name__ == '__main__':
    check_100g_caps()

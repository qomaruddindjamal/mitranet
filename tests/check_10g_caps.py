import paramiko

def check_10g_caps():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')

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

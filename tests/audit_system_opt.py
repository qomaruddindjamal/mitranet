import paramiko
import sys

def audit_system():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')

    commands = [
        ('OS & Kernel', 'uname -a && cat /etc/os-release | grep PRETTY_NAME'),
        ('RAM Usage', 'free -h'),
        ('Disk Breakdown', 'df -hT / && du -sh /* 2>/dev/null | sort -hr | head -n 12'),
        ('Active Systemd Services', 'systemctl list-units --type=service --state=running --no-pager'),
        ('Boot Time Blame', 'systemd-analyze blame | head -n 12'),
        ('Journald Size', 'journalctl --disk-usage'),
        ('Network Sysctl', 'sysctl net.ipv4.ip_forward net.ipv4.tcp_congestion_control net.core.somaxconn net.core.rmem_max net.core.wmem_max vm.swappiness vm.vfs_cache_pressure 2>/dev/null'),
        ('Apt Cache and lists', 'du -sh /var/cache/apt /var/lib/apt/lists 2>/dev/null'),
        ('Installed deb packages', 'dpkg -l | grep "^ii" | wc -l'),
        ('Check huge files over 100M', 'find / -xdev -type f -size +100M 2>/dev/null')
    ]

    for title, cmd in commands:
        stdin, stdout, stderr = c.exec_command(cmd)
        raw = stdout.read().decode('utf-8', errors='replace')
        sys.stdout.buffer.write(f"\n=== {title} ===\n{raw}\n".encode('utf-8'))

    c.close()

if __name__ == '__main__':
    audit_system()

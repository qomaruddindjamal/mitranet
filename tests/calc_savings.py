import paramiko

def calculate_savings():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')

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

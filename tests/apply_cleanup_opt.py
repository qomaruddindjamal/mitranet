import paramiko

def apply_cleanup_and_stability():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')

    # 1. Disable apt-daily timer and service
    c.exec_command('systemctl disable --now apt-daily.timer apt-daily-upgrade.timer apt-daily.service apt-daily-upgrade.service')

    # 2. Clean apt archives cache
    c.exec_command('apt-get clean')

    # 3. Configure journald retention limit safely
    journald_conf = "[Journal]\nSystemMaxUse=50M\nSystemKeepFree=100M\n"
    sftp = c.open_sftp()
    try:
        sftp.mkdir('/etc/systemd/journald.conf.d')
    except Exception:
        pass
    with sftp.open('/etc/systemd/journald.conf.d/00-mitranet-retention.conf', 'w') as jf:
        jf.write(journald_conf)
    sftp.close()

    c.exec_command('systemctl restart systemd-journald && journalctl --vacuum-size=50M')

    # 4. Verify status
    _, out1, _ = c.exec_command('systemctl is-enabled apt-daily.timer apt-daily-upgrade.timer 2>&1')
    _, out2, _ = c.exec_command('du -sh /var/cache/apt')
    _, out3, _ = c.exec_command('journalctl --disk-usage')
    _, out4, _ = c.exec_command('uptime')

    print("=== HASIL OPTIMASI & KEAMANAN ===")
    print("Apt Daily Timers:", out1.read().decode().strip())
    print("Ukuran Apt Cache:", out2.read().decode().strip())
    print("Ukuran System Journal:", out3.read().decode().strip())
    print("Status Uptime Router:", out4.read().decode().strip())

    c.close()

if __name__ == '__main__':
    apply_cleanup_and_stability()

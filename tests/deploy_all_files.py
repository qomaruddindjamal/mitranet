import paramiko

def sync_and_test():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')
    
    sftp = c.open_sftp()
    
    # 1. Upload files to both /mitranet/web and /usr/share/mitranet/web
    files = [
        ('c:/mitranet/src/api/server.py', '/usr/lib/python3/dist-packages/mitranet/src/api/server.py'),
        ('c:/mitranet/web/services_kvm.php', '/mitranet/web/services_kvm.php'),
        ('c:/mitranet/web/services_virtual.php', '/mitranet/web/services_virtual.php'),
        ('c:/mitranet/web/includes/head.inc', '/mitranet/web/includes/head.inc'),
        ('c:/mitranet/web/includes/api.inc', '/mitranet/web/includes/api.inc'),
        
        ('c:/mitranet/web/services_kvm.php', '/usr/share/mitranet/web/services_kvm.php'),
        ('c:/mitranet/web/services_virtual.php', '/usr/share/mitranet/web/services_virtual.php'),
        ('c:/mitranet/web/includes/head.inc', '/usr/share/mitranet/web/includes/head.inc'),
        ('c:/mitranet/web/includes/api.inc', '/usr/share/mitranet/web/includes/api.inc')
    ]
    
    for src, dst in files:
        sftp.put(src, dst)
        print(f"Uploaded {src} -> {dst}")
        
    sftp.close()
    
    # Restart webui service
    stdin, stdout, stderr = c.exec_command("systemctl restart mitranet-webui")
    print("Restarting mitranet-webui...")
    stdout.channel.recv_exit_status()
    
    # Check status
    stdin, stdout, stderr = c.exec_command("systemctl is-active mitranet-webui")
    print("mitranet-webui status:", stdout.read().decode().strip())
    
    c.close()

if __name__ == '__main__':
    sync_and_test()

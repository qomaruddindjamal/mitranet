import paramiko

def check_webserver():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')
    
    stdin, stdout, stderr = c.exec_command("systemctl list-units --type=service --state=running | grep -E 'web|php|http|nginx|caddy|lighttpd|mitranet'")
    print("RUNNING_SERVICES:\n", stdout.read().decode())
    
    stdin, stdout, stderr = c.exec_command("ss -tlpn")
    print("LISTENING_PORTS:\n", stdout.read().decode())
    
    c.close()

if __name__ == '__main__':
    check_webserver()

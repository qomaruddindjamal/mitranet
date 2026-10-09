import paramiko

def check_nginx():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')
    
    stdin, stdout, stderr = c.exec_command("grep -rn 'root ' /etc/nginx/ /etc/lighttpd/ /etc/apache2/ 2>/dev/null")
    print("ROOT_SEARCH:\n", stdout.read().decode())
    
    stdin, stdout, stderr = c.exec_command("find / -name 'services_virtual.php' 2>/dev/null")
    print("SERVICES_VIRTUAL_FILES:\n", stdout.read().decode())
    
    c.close()

if __name__ == '__main__':
    check_nginx()

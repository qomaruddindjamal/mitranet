import paramiko

def check():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')
    
    # Test reading guest aaPanel credentials from host VM
    cmd = """
sshpass -p 'K0323205' ssh -o StrictHostKeyChecking=no -o ConnectTimeout=5 root@192.168.101.118 "
cat /www/server/panel/default.pl; echo '===';
cat /www/server/panel/data/admin_path.pl; echo '===';
cat /www/server/panel/data/port.pl; echo '===';
sqlite3 /www/server/panel/data/default.db 'SELECT username FROM users LIMIT 1;'
"
"""
    stdin, stdout, stderr = c.exec_command(cmd)
    out = stdout.read().decode()
    err = stderr.read().decode()
    print("OUTPUT:\n" + out)
    print("ERR:\n" + err)
    c.close()

if __name__ == '__main__':
    check()

import paramiko

def check():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')
    
    cmd = """
ssh -o StrictHostKeyChecking=no root@192.168.101.118 "sed -n '445,465p' /www/server/panel/class/config.py"
"""
    stdin, stdout, stderr = c.exec_command(cmd)
    res = stdout.read().decode('utf-8', errors='ignore')
    print("config.py setPassword:\n", res.encode('ascii', errors='ignore').decode('ascii'))
    c.close()

if __name__ == '__main__':
    check()

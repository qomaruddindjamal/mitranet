import paramiko

def check():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')
    
    cmd = """
python3 -c "
import subprocess
try:
    res = subprocess.run(['ssh', '-o', 'StrictHostKeyChecking=no', '-o', 'ConnectTimeout=3', 'root@192.168.101.118',
        '''python3 -c \\\"
import sqlite3, os
pwd = ''
if os.path.exists('/www/server/panel/default.pl'):
    pwd = open('/www/server/panel/default.pl').read().strip()
path = ''
if os.path.exists('/www/server/panel/data/admin_path.pl'):
    path = open('/www/server/panel/data/admin_path.pl').read().strip()
user = ''
try:
    conn = sqlite3.connect('/www/server/panel/data/default.db')
    cur = conn.cursor()
    cur.execute('SELECT username FROM users LIMIT 1;')
    row = cur.fetchone()
    if row:
        user = row[0]
except Exception as e:
    user = str(e)
print(f'USER:{user}|PWD:{pwd}|PATH:{path}')
\\\"'''
    ], capture_output=True, text=True, timeout=5)
    print('GUEST_RESULT:', res.stdout.strip())
    print('GUEST_ERR:', res.stderr.strip())
except Exception as e:
    print('ERR:', e)
"
"""
    stdin, stdout, stderr = c.exec_command(cmd)
    print(stdout.read().decode())
    c.close()

if __name__ == '__main__':
    check()

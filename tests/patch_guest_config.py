import paramiko

def check():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')
    
    cmd = """
ssh -o StrictHostKeyChecking=no root@192.168.101.118 'python3 -c "
p = \\"/www/server/panel/class/config.py\\"
lines = open(p).readlines()
target = \\"public.M(\'users\').where(\\"username=?\\",(session[\'username\'],)).setField(\'password\',public.password_salt(public.md5(get.password1.strip()),username=session[\'username\']))\\\\n\\"
injected = False
for idx, line in enumerate(lines):
    if target in line:
        patch = \\"        try:\\\\n            public.writeFile(public.get_panel_path() + \'/default.pl\', get.password1.strip())\\\\n        except Exception:\\\\n            pass\\\\n\\"
        lines.insert(idx + 1, patch)
        injected = True
        break
if injected:
    open(p, \\"w\\").writelines(lines)
    print(\\"CONFIG_PY_PATCHED_SUCCESS\\")
else:
    print(\\"TARGET_NOT_FOUND\\")
"'
"""
    stdin, stdout, stderr = c.exec_command(cmd)
    res = stdout.read().decode('utf-8', errors='ignore')
    print("RESULT:\n", res.encode('ascii', errors='ignore').decode('ascii'))
    
    # Reload bt panel in guest
    cmd_reload = "ssh -o StrictHostKeyChecking=no root@192.168.101.118 '/etc/init.d/bt reload'"
    stdin, stdout, stderr = c.exec_command(cmd_reload)
    print("RELOAD:\n", stdout.read().decode('utf-8', errors='ignore'))
    
    c.close()

if __name__ == '__main__':
    check()

import paramiko
import requests

def test_full_flow():
    # 1. Verify initial credentials from MitraNet WebUI
    s = requests.Session()
    s.post('http://192.168.56.101:8443/login.php', data={'username': 'admin', 'password': 'mitranet'}, allow_redirects=True)
    r1 = s.get('http://192.168.56.101:8443/services_virtual.php?act=manage_aapanel')
    assert 'mitranet123' in r1.text, "Initial password not found in WebUI"
    assert 'value="mitranet"' in r1.text, "Initial username not found in WebUI"
    print(">>> Step 1 Passed: Initial default credentials visible in WebUI")

    # 2. Simulate user changing password & username inside aaPanel
    # (aaPanel's tools.py panel <new_pwd> and set_panel_username)
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')
    
    # Change username to mitraadmin and password to SuperPass999!
    change_cmd = """
ssh -o StrictHostKeyChecking=no root@192.168.101.118 "
/www/server/panel/pyenv/bin/python3 -c '
import sys
sys.path.insert(0, \"/www/server/panel/class\")
import public, db
sql = db.Sql()
sql.table(\"users\").where(\"id=?\", (1,)).setField(\"username\", \"mitraadmin\")
sql.table(\"users\").where(\"id=?\", (1,)).setField(\"password\", public.password_salt(public.md5(\"SuperPass999!\"), uid=1))
public.writeFile(\"/www/server/panel/default.pl\", \"SuperPass999!\")
'
"
"""
    stdin, stdout, stderr = c.exec_command(change_cmd)
    stdout.read()
    print(">>> Step 2 Passed: Changed credentials inside aaPanel to mitraadmin / SuperPass999!")

    # 3. Verify that MitraNet WebUI immediately reflects the new credentials
    r2 = s.get('http://192.168.56.101:8443/services_virtual.php?act=manage_aapanel')
    assert 'SuperPass999!' in r2.text, "New password SuperPass999! not reflected in WebUI"
    assert 'value="mitraadmin"' in r2.text, "New username mitraadmin not reflected in WebUI"
    print(">>> Step 3 Passed: WebUI dynamically reflected new credentials!")

    # 4. Revert credentials back to default (mitranet / mitranet123)
    revert_cmd = """
ssh -o StrictHostKeyChecking=no root@192.168.101.118 "
/www/server/panel/pyenv/bin/python3 -c '
import sys
sys.path.insert(0, \"/www/server/panel/class\")
import public, db
sql = db.Sql()
sql.table(\"users\").where(\"id=?\", (1,)).setField(\"username\", \"mitranet\")
sql.table(\"users\").where(\"id=?\", (1,)).setField(\"password\", public.password_salt(public.md5(\"mitranet123\"), uid=1))
public.writeFile(\"/www/server/panel/default.pl\", \"mitranet123\")
'
"
"""
    stdin, stdout, stderr = c.exec_command(revert_cmd)
    stdout.read()
    c.close()

    # 5. Verify revert back to default
    r3 = s.get('http://192.168.56.101:8443/services_virtual.php?act=manage_aapanel')
    assert 'mitranet123' in r3.text, "Reverted password mitranet123 not in WebUI"
    assert 'value="mitranet"' in r3.text, "Reverted username mitranet not in WebUI"
    print(">>> Step 5 Passed: Successfully verified dynamic reflection and restored to mitranet / mitranet123!")

if __name__ == '__main__':
    test_full_flow()

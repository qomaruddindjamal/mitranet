import paramiko
import requests
import json
import time

def verify_live():
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect('192.168.56.101', username='root', password='mitranet')

    # 1. Read real credentials directly from guest VM
    cmd = 'ssh -o StrictHostKeyChecking=no root@192.168.101.118 "cat /www/server/panel/default.pl; echo \'---\'; sqlite3 /www/server/panel/data/default.db \'SELECT username FROM users;\'"'
    stdin, stdout, stderr = c.exec_command(cmd)
    guest_out = stdout.read().decode('utf-8', errors='ignore').strip().split('---')
    guest_pwd = guest_out[0].strip()
    guest_user = guest_out[1].strip() if len(guest_out) > 1 else ''
    print(f"REAL GUEST ACTUAL CREDENTIALS -> User: '{guest_user}', Pass: '{guest_pwd}'")

    # 2. Query MitraNet WebUI via API
    s = requests.Session()
    s.post('http://192.168.56.101:8443/login.php', data={'username': 'admin', 'password': 'mitranet'}, allow_redirects=True)
    r_api = s.get('http://192.168.56.101:8443/api/v1/services/kvm')
    creds_api = r_api.json()['vms'][0]['aapanel_creds']
    print(f"MITRANET API RETURNED CREDENTIALS -> User: '{creds_api.get('username')}', Pass: '{creds_api.get('password')}'")

    assert creds_api['username'] == guest_user, f"Username mismatch! API: {creds_api['username']} vs Guest: {guest_user}"
    assert creds_api['password'] == guest_pwd, f"Password mismatch! API: {creds_api['password']} vs Guest: {guest_pwd}"
    print(">>> SUCCESS: MitraNet WebUI API is reading 100% genuine real-time credentials from aaPanel!")

    # 3. Test changing aaPanel credential inside guest to a new random password
    new_random_pass = 'AcakPass_' + str(int(time.time()))[-4:]
    new_random_user = 'user_' + str(int(time.time()))[-4:]
    print(f"\nSimulating change inside aaPanel to random: {new_random_user} / {new_random_pass}...")
    change_cmd = f"""ssh -o StrictHostKeyChecking=no root@192.168.101.118 "
/www/server/panel/pyenv/bin/python3 -c '
import sys
sys.path.insert(0, \\"/www/server/panel/class\\")
import public, db
sql = db.Sql()
sql.table(\\"users\\").where(\\"id=?\\", (1,)).setField(\\"username\\", \\"{new_random_user}\\")
sql.table(\\"users\\").where(\\"id=?\\", (1,)).setField(\\"password\\", public.password_salt(public.md5(\\"{new_random_pass}\\"), uid=1))
public.writeFile(\\"/www/server/panel/default.pl\\", \\"{new_random_pass}\\")
'
" """
    stdin, stdout, stderr = c.exec_command(change_cmd)
    stdout.read()

    # 4. Check API again
    r_api2 = s.get('http://192.168.56.101:8443/api/v1/services/kvm')
    creds_api2 = r_api2.json()['vms'][0]['aapanel_creds']
    print(f"AFTER CHANGE -> User: '{creds_api2.get('username')}', Pass: '{creds_api2.get('password')}'")
    assert creds_api2['username'] == new_random_user, "Random username not reflected!"
    assert creds_api2['password'] == new_random_pass, "Random password not reflected!"
    print(">>> SUCCESS: Real random change inside aaPanel immediately reflected in MitraNet WebUI!")

    # 5. Restore back to mitranet / mitranet123 as requested by user
    print("\nRestoring default credentials to mitranet / mitranet123...")
    restore_cmd = """ssh -o StrictHostKeyChecking=no root@192.168.101.118 "
/www/server/panel/pyenv/bin/python3 -c '
import sys
sys.path.insert(0, \\"/www/server/panel/class\\")
import public, db
sql = db.Sql()
sql.table(\\"users\\").where(\\"id=?\\", (1,)).setField(\\"username\\", \\"mitranet\\")
sql.table(\\"users\\").where(\\"id=?\\", (1,)).setField(\\"password\\", public.password_salt(public.md5(\\"mitranet123\\"), uid=1))
public.writeFile(\\"/www/server/panel/default.pl\\", \\"mitranet123\\")
'
" """
    stdin, stdout, stderr = c.exec_command(restore_cmd)
    stdout.read()

    r_api3 = s.get('http://192.168.56.101:8443/api/v1/services/kvm')
    creds_api3 = r_api3.json()['vms'][0]['aapanel_creds']
    print(f"RESTORED -> User: '{creds_api3.get('username')}', Pass: '{creds_api3.get('password')}'")
    assert creds_api3['username'] == 'mitranet'
    assert creds_api3['password'] == 'mitranet123'
    print(">>> ALL VERIFICATIONS COMPLETED SUCCESSFULLY!")

    c.close()

if __name__ == '__main__':
    verify_live()

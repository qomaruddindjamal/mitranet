import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("172.21.162.163", port=22, username="root", password="pfsense")

cmd = """python3 -c "import sqlite3; con=sqlite3.connect('/var/db/pkg/repos/pfSense-core/db'); print(con.execute('SELECT name, version FROM packages').fetchall())" """
stdin, stdout, stderr = client.exec_command(cmd)
print("STDOUT:", stdout.read().decode())
print("STDERR:", stderr.read().decode())

cmd2 = """python3 -c "import sqlite3; con=sqlite3.connect('/var/db/pkg/repos/pfSense/db'); print(len(con.execute('SELECT name FROM packages').fetchall()))" """
stdin, stdout, stderr = client.exec_command(cmd2)
print("PFSENSE PKG COUNT:", stdout.read().decode())
print("PFSENSE ERR:", stderr.read().decode())

client.close()

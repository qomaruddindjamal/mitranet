import subprocess
import os

q_cmd = [
    "ssh", "-o", "StrictHostKeyChecking=no", "-o", "ConnectTimeout=2",
    "root@192.168.101.118",
    "cat /www/server/panel/default.pl 2>/dev/null; echo '---'; "
    "cat /www/server/panel/data/admin_path.pl 2>/dev/null; echo '---'; "
    "/www/server/panel/pyenv/bin/python3 -c \"import sqlite3; conn = sqlite3.connect('/www/server/panel/data/default.db'); print(conn.cursor().execute('SELECT username FROM users LIMIT 1;').fetchone()[0])\" 2>/dev/null"
]
q_res = subprocess.run(q_cmd, capture_output=True, text=True, timeout=5)
print("STDOUT:\n", q_res.stdout)
print("STDERR:\n", q_res.stderr)
print("RET:", q_res.returncode)

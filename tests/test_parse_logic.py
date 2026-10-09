import subprocess
import os

target_ip = "192.168.101.118"
q_cmd = [
    "ssh", "-o", "StrictHostKeyChecking=no", "-o", "ConnectTimeout=3",
    f"root@{target_ip}",
    "cat /www/server/panel/default.pl 2>/dev/null; echo '===AAPANEL_SPLIT==='; "
    "cat /www/server/panel/data/admin_path.pl 2>/dev/null; echo '===AAPANEL_SPLIT==='; "
    "/www/server/panel/pyenv/bin/python3 -c \"import sqlite3; conn = sqlite3.connect('/www/server/panel/data/default.db'); print(conn.cursor().execute('SELECT username FROM users LIMIT 1;').fetchone()[0])\" 2>/dev/null"
]
q_res = subprocess.run(q_cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=5)
print("RETURNCODE:", q_res.returncode)
print("STDOUT:", repr(q_res.stdout))
print("STDERR:", repr(q_res.stderr))
if q_res.returncode == 0 and "===AAPANEL_SPLIT===" in q_res.stdout:
    parts = q_res.stdout.split("===AAPANEL_SPLIT===")
    print("PARTS:", [p.strip() for p in parts])

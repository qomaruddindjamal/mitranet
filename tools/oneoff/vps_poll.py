"""Poll status deploy di VPS tiap N detik (dipakai saat rebuild berjalan)."""
import sys
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[2]))
import mitranet_secrets as s  # noqa: E402
import paramiko  # noqa: E402

ROUNDS = int(sys.argv[1]) if len(sys.argv) > 1 else 12
DELAY = int(sys.argv[2]) if len(sys.argv) > 2 else 10
CMD = r"""
date +%H:%M:%S
if pgrep -f 'vps-bootstrap.sh' >/dev/null; then
  echo "BOOTSTRAP=RUNNING $(pgrep -af 'vps-bootstrap.sh' | head -1 | awk '{print $NF}')"
else
  echo BOOTSTRAP=IDLE
fi
systemctl is-active mitranet-webui xl2tpd strongswan strongswan-swanctl wg-quick@wg0 2>&1 | paste -sd, -
echo "tcp_8443_8000=$(ss -Hlnt | grep -cE ':8443|:8000')"
echo "udp_tunnels=$(ss -Hlnu | grep -cE ':1701|:13231|:51831|:51832|:4500')"
tail -3 /tmp/payload/lastlog 2>/dev/null
journalctl -u mitranet-webui -n 2 --no-pager 2>/dev/null | tail -2
"""
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
for i in range(1, ROUNDS + 1):
    try:
        c.connect(s.VPS_HOST, username=s.VPS_USER, key_filename=s.VPS_SSH_KEY,
                  timeout=10, banner_timeout=20, auth_timeout=20,
                  look_for_keys=False, allow_agent=False)
        _, out, err = c.exec_command(CMD, timeout=30)
        txt = (out.read() + err.read()).decode("utf-8", "replace").strip()
        print(f"--- [{i}/{ROUNDS}] " + " | ".join(txt.splitlines()), flush=True)
        c.close()
    except Exception as e:  # noqa: BLE001
        print(f"--- [{i}/{ROUNDS}] SSH-ERR {type(e).__name__}", flush=True)
    if i < ROUNDS:
        time.sleep(DELAY)

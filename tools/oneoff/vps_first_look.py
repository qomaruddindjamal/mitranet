import socket
import sys

sys.path.insert(0, r"C:\mitranet")
import mitranet_secrets as s  # noqa: E402
import paramiko  # noqa: E402

c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ok = None
for label, kw in (("key", {"key_filename": s.VPS_SSH_KEY}),
                  ("password", {"password": s.VPS_PASS})):
    try:
        c.connect(s.VPS_HOST, username=s.VPS_USER, timeout=20, banner_timeout=30,
                  auth_timeout=30, look_for_keys=False, allow_agent=False, **kw)
        ok = label
        break
    except Exception as e:  # noqa: BLE001
        print(f"auth via {label} gagal: {type(e).__name__}: {str(e)[:90]}")
if not ok:
    raise SystemExit(1)
print("AUTH OK via", ok)
CMDS = [
    "id -un; . /etc/os-release; echo $PRETTY_NAME; uname -m; nproc; free -m | awk '/Mem/{print $2\" MiB\"}'",
    "ip -4 -o addr show scope global | awk '{print $2,$4}'; ip -4 route | head -3",
    "sudo -n true 2>&1 && echo SUDO_NOPASSWD_OK || echo SUDO_NEEDS_PASSWORD",
    "systemctl is-enabled ssh 2>&1; ss -Hlnt | head -10",
    "df -h / | tail -1",
    "command -v php || echo no-php; command -v wg || echo no-wg; command -v xl2tpd || no=1; dpkg -l | grep -cE '^ii.*(php|xl2tpd|strongswan|wireguard)'",
]
for cmd in CMDS:
    _, out, err = c.exec_command(cmd, timeout=45)
    txt = (out.read() + err.read()).decode("utf-8", "replace")
    print("### " + cmd[:52])
    print(txt.strip()[:700] or "(kosong)")
c.close()

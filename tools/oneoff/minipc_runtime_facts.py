"""One-off: baca fakta runtime Mini PC yang dipakai untuk menyusun kit rebuild VPS.

Pemakaian: python tools/oneoff/minipc_runtime_facts.py <ip-mini-pc>
"""
import socket
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[2]))
import mitranet_secrets as s  # noqa: E402
import paramiko  # noqa: E402

if len(sys.argv) < 2:
    raise SystemExit("usage: minipc_runtime_facts.py <ip-mini-pc>")
HOST = sys.argv[1]
CMDS = [
    "ls -ld /mitranet; readlink -f /mitranet",
    "grep -n 'web' /usr/share/mitranet/mitranet/src/api/server.py | head -25",
    "grep -rn '8000' /usr/share/mitranet/mitranet/src/api/*.py | head -12",
    "pid=$(pgrep -f 'mitranet.src.api.server' | head -1); "
    "tr '\\0' '\\n' < /proc/$pid/environ | grep -c MITRANET_API_SECRET",
    "systemctl list-units --type=service --no-pager --no-legend | awk '{print $1}' | grep -iE 'mitranet|xray|xl2tp|swan' | head -20",
    "dpkg -l | awk '/^ii/{print $2\" \"$3}' | grep -iE 'php|wireguard|strongswan|xl2tpd|fping|nftables|python3-(pydantic|yaml|requests|netifaces)' | head -25",
]


def main() -> None:
    sock = socket.create_connection((HOST, 22), 20)
    t = paramiko.Transport(sock)
    t.connect()
    t.auth_password(s.MINIPC_USER, s.MINIPC_PASS)
    for c in CMDS:
        ch = t.open_session()
        ch.settimeout(30)
        ch.exec_command(c)
        buf = b""
        while not ch.exit_status_ready():
            if ch.recv_ready():
                buf += ch.recv(4096)
        buf += ch.recv(65535)
        print("### " + c.split("|")[0][:60])
        print(buf.decode("utf-8", "replace").strip()[:1800])
        print()
    t.close()


if __name__ == "__main__":
    main()

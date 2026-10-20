"""Arahkan tunnel WireGuard Mini PC ke VPS hub hasil rebuild, lalu verifikasi.

Pemakaian:
    python tools/ops/minipc_point_to_vps.py <ip-minipc> <pub-wg0> [pub-boost1 pub-boost2]
    # batalkan saja (tanpa pub key):  python tools/ops/minipc_point_to_vps.py <ip-minipc> --off

Aman terhadap blackhole: tunnel hanya diaktifkan kalau setelah restart ada hasil
handshake DAN HTTPS eksternal jalan; kalau tidak, tunnel dimatikan lagi otomatis.
"""
import socket
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[2]))
import mitranet_secrets as s  # noqa: E402
import paramiko  # noqa: E402

MAP = {"wg0": ("13231", "10.10.77."), "wgboost1": ("51831", "10.250.1."),
       "wgboost2": ("51832", "10.250.2.")}


def connect(host):
    c = paramiko.SSHClient()
    c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    c.connect(host, username=s.MINIPC_USER, password=s.MINIPC_PASS, timeout=25,
              banner_timeout=40, auth_timeout=40, look_for_keys=False, allow_agent=False)
    return c


def run(c, cmd, timeout=90):
    _, out, err = c.exec_command(cmd, timeout=timeout)
    return (out.read() + err.read()).decode("utf-8", "replace").strip()


def main():
    if len(sys.argv) < 2:
        raise SystemExit(__doc__)
    host = sys.argv[1]
    c = connect(host)
    print("Mini PC:", run(c, "hostname; ip -4 -o addr show scope global | awk '{print $2,$4}' | head -3"))

    if len(sys.argv) >= 3 and sys.argv[2] == "--off":
        for t in MAP:
            run(c, f"systemctl stop wg-quick@{t} 2>&1")
        print("semua wg-quick dimatikan; ", run(c, "curl -s -o /dev/null -w 'web=%{http_code}' --max-time 8 https://www.google.com"))
        return 0

    keys = sys.argv[2:]
    for i, (tun, (port, _)) in enumerate(MAP.items()):
        if i >= len(keys):
            break
        pub = keys[i]
        conf = f"/etc/wireguard/{tun}.conf"
        out = run(c, f"""
            test -f {conf} || {{ echo '{conf} tidak ada'; exit 0; }}
            cp -f {conf} {conf}.bak-$(date +%Y%m%d%H%M)
            awk -v pub='{pub}' '
              /^\\[Peer\\]/ {{inpeer=1}}
              /^[Pp]ublicKey[ ]*=/ {{ if (inpeer) {{ print "PublicKey = " pub; next }} }}
              {{print}}
            ' {conf} > {conf}.new && mv {conf}.new {conf} && chmod 600 {conf}
            grep -E 'PublicKey|Endpoint|AllowedIPs' {conf} | head -6
        """)
        print(f"--- {tun}\n{out}")

    print("\nverifikasi handshake:")
    print(run(c, "for t in " + " ".join(MAP) + "; do systemctl restart wg-quick@$t 2>&1; done; sleep 8; wg show 2>/dev/null | grep -E 'interface|peer|endpoint|latest handshake' | head -24"))
    web = run(c, "curl -s -o /dev/null -w '%{http_code} %{time_total}s' --max-time 10 https://www.google.com")
    via = run(c, "ip route get 8.8.8.8 | head -1")
    print(f"web lewat tunnel: {web} | route: {via}")
    if web.startswith("2") or web.startswith("3"):
        print("OK: tunnel hidup dan internet jalan. Exit IP:",
              run(c, "curl -s --max-time 8 https://api.ipify.org"))
    else:
        for t in MAP:
            run(c, f"systemctl stop wg-quick@{t}; systemctl disable wg-quick@{t} 2>/dev/null")
        print("Internet TIDAK jalan lewat tunnel -> wg-quick dimatikan lagi (anti-blackhole).")
        print("cek:", run(c, "curl -s -o /dev/null -w 'web=%{http_code}' --max-time 8 https://www.google.com"))
    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

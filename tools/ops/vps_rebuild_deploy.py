"""Rebuild-deploy runner for the MitraNet VPS hub (Debian 13).

Dipakai setelah VPS di-reinstall di panel provider. Kredensial dibaca dari
mitranet_secrets.py + .env (tidak ada IP/password hardcode di sini).

Pemakaian:
    python tools/ops/vps_rebuild_deploy.py --wait            # tunggu SSH hidup
    python tools/ops/vps_rebuild_deploy.py --phases deps,app,net
    python tools/ops/vps_rebuild_deploy.py --phases fw --confirm-firewall
    python tools/ops/vps_rebuild_deploy.py --status

Firewall HANYA dipasang dengan --confirm-firewall: setelah apply, SSH diverifikasi
dari luar; kalau mati, watchdog di VPS akan flush ruleset sendiri.
"""
from __future__ import annotations

import argparse
import os
import socket
import sys
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[2]))
import mitranet_secrets as s  # noqa: E402

try:
    import paramiko  # noqa: E402
except ImportError:
    paramiko = None

REPO = Path(__file__).resolve().parents[2]
BOOTSTRAP = REPO / "tools/ops/vps-bootstrap.sh"
PAYLOAD = [
    REPO / "backups/minipc/2026-10-20/minipc-staging.tar.gz",
    REPO / "backups/minipc/payload/staging/xray-bin",
]
REMOTE_PAYLOAD = "/tmp/payload"


def connect(host: str, user: str, password: str, port: int = 22,tries: int = 3):
    last = None
    for _ in range(tries):
        try:
            c = paramiko.SSHClient()
            c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            c.connect(host, port=port, username=user, password=password,
                      timeout=25, banner_timeout=40, auth_timeout=40,
                      look_for_keys=False, allow_agent=False)
            return c
        except Exception as e:  # noqa: BLE001
            last = e
            time.sleep(3)
    raise SystemExit(f"SSH gagal ke {host}:{port}: {type(last).__name__}: {last}")


def run(c, cmd: str, timeout: int = 300) -> tuple[int, str]:
    _, out, err = c.exec_command(cmd, timeout=timeout)
    data = (out.read() + err.read()).decode("utf-8", "replace")
    return out.channel.recv_exit_status(), data


def sudo_run(c, user: str, password: str, script: str, timeout: int = 1800):
    if user == "root":
        _in, out, err = c.exec_command("bash -s", timeout=timeout)
    else:
        _in, out, err = c.exec_command("sudo -S -p '' bash -s", timeout=timeout)
        _in.write(password + "\n")
        _in.flush()
    _in.write(script)
    _in.channel.shutdown_write()
    data = (out.read() + err.read()).decode("utf-8", "replace")
    rc = out.channel.recv_exit_status()
    return rc, data


def port_open(host: str, port: int = 22, wait: float = 4.0) -> bool:
    try:
        socket.create_connection((host, port), wait).close()
        return True
    except OSError:
        return False


def upload(c, local: Path, remote: str) -> None:
    tmp = remote + ".part"
    sftp = c.open_sftp()
    sftp.put(str(local), tmp)
    sftp.chmod(tmp, 0o600)
    sftp.close()
    run(c, f"mv -f {tmp} {remote}")
    print(f"   uploaded {local.name} -> {remote} ({local.stat().st_size/1e6:.1f} MB)")


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--host", default=None)
    ap.add_argument("--user", default=None)
    ap.add_argument("--wait", type=int, default=0, metavar="DETIK",
                    help="tunggu port 22 hidup maksimal N detik")
    ap.add_argument("--phases", default="status",
                    help="daftar phase: deps,app,net,fw,status (pisahkan koma)")
    ap.add_argument("--confirm-firewall", action="store_true",
                    help="wajib untuk phase fw")
    ap.add_argument("--no-payload", action="store_true",
                    help="lewati upload payload (sudah ada di VPS)")
    args = ap.parse_args()

    if paramiko is None:
        raise SystemExit("paramiko tidak tersedia: pip install paramiko")

    host = args.host or s.VPS_HOST
    user = args.user or s.VPS_USER
    password = s.VPS_PASS
    phases = [p.strip() for p in args.phases.split(",") if p.strip()]

    if args.wait:
        deadline = time.time() + args.wait
        while time.time() < deadline:
            if port_open(host):
                print(f"SSH {host}:22 hidup")
                break
            print(".", end="", flush=True)
            time.sleep(5)
        else:
            print(f"\nSSH {host}:22 tetap mati setelah {args.wait}s")
            return 2

    c = connect(host, user, password)
    rc, who = run(c, "id -un; . /etc/os-release; echo $PRETTY_NAME; ip -4 -o addr show scope global | awk '{print $2,$4}'")
    print("== sesi:", " ".join(who.split())[:200])
    c.close()

    if "fw" in phases and not args.confirm_firewall:
        raise SystemExit("Phase fw butuh --confirm-firewall (risiko lockout).")

    c = connect(host, user, password)

    if not args.no_payload and any(p in phases for p in ("deps", "app", "net")):
        run(c, f"mkdir -p {REMOTE_PAYLOAD}")
        upload(c, BOOTSTRAP, f"{REMOTE_PAYLOAD}/vps-bootstrap.sh")
        for f in PAYLOAD:
            if f.exists():
                upload(c, f, f"{REMOTE_PAYLOAD}/{f.name}")
            else:
                print(f"   WARN: payload hilang: {f}")

    env = (f"PUB_IP={host} LAN_IF={os.environ.get('VPS_LAN_IF', 'eth0')} "
           f"L2TP_PSK='{s.L2TP_PSK}'")
    for p in phases:
        if p == "status":
            rc, out = sudo_run(c, user, password,
                               f"{env} bash {REMOTE_PAYLOAD}/vps-bootstrap.sh status")
            print(f"== status (rc={rc})\n{out}")
            continue
        if p == "fw":
            rc, out = sudo_run(c, user, password,
                               f"{env} bash {REMOTE_PAYLOAD}/vps-bootstrap.sh fw")
            print(f"== fw (rc={rc})\n{out}")
            time.sleep(3)
            if port_open(host):
                rc2, out2 = sudo_run(c, user, password,
                                     "systemctl cancel fw-watchdog 2>&1; "
                                     "echo WATCHDOG_CANCELLED; nft list ruleset | head -4")
                print(f"== SSH dari luar HIDUP -> watchdog dibatalkan\n{out2}")
            else:
                print("!! SSH dari luar MATI setelah apply firewall. "
                      "Watchdog 120s akan flush ruleset; tunggu 3 menit lalu cek lagi.")
            continue
        rc, out = sudo_run(c, user, password,
                           f"{env} bash {REMOTE_PAYLOAD}/vps-bootstrap.sh {p}")
        print(f"== phase {p} (rc={rc})\n{out[-3000:]}")
        if not port_open(host):
            print(f"!! SSH mati setelah phase {p} — hentikan, jangan lanjut phase.")
            return 3

    c.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

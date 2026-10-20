# Runbook Rebuild VPS 103.93.162.168 (Debian 13) — 2026-10-20

Status lama: VPS tidak bisa diakses total (ICMP/22/8443/13231 mati), konsol provider
tidak bisa dibuka. Satu-satunya jalur pulih = reinstall OS dari panel.

## 1. Pilihan di panel provider
- OS: **Debian 13 (trixie)**, versi minimal / ISO distro (bukan image "web server").
- IP: tetap **103.93.162.168**, jangan ganti paket / jangan ubah RAM-disk.
- Akses: aktifkan **SSH password login** untuk user `root` (atau user awal yang diberikan panel).
- Jangan pasang "security hardening / firewall / fail2ban" bawaan panel — nanti bentrok.
- Catat password root yang dipakai panel → isi ke `.env`: `MITRANET_VPS_USER`, `MITRANET_VPS_PASS`.

## 2. Deploy dari laptop (satu-perintah per fase)
```bash
python tools/ops/vps_rebuild_deploy.py --wait 900 --phases deps,app,net
python tools/ops/vps_rebuild_deploy.py --status
python tools/ops/vps_rebuild_deploy.py --phases fw --confirm-firewall
```
`tools/ops/vps-bootstrap.sh` = sisi VPS (idempotent, phase `deps|app|net|fw|status`).

## 3. Aturan anti-lockout (penyebab outage sebelumnya)
1. Firewall **tidak ikut** `all`; hanya fase `fw` dan wajib `--confirm-firewall`.
2. Runner upload ruleset → `nft -c` (cek kompilasi) → pasang watchdog `systemd-run
   --on-active=120s nft flush ruleset` → baru `nft -f`.
3. Setelah apply, laptop mengecek port 22 dari luar; hidup → `systemctl cancel fw-watchdog`,
   mati → watchdog flush sendiri dalam 120 detik.
4. `systemctl disable nftables`: ruleset **tidak persisten di boot** → reboot dari panel
   selalu mengembalikan akses.
5. Semua `systemctl restart` dibungkus `timeout 90` (pembunuh `swanctl --load-all` yang
   menggantung adalah dugaan penyebab outage).
6. SSH rule ada di baris ke-3 chain input (setelah `lo` dan `established`), tanpa drop sebelumnya.

## 4. Nilai jaringan yang dipakai rebuild ini
| Item | Nilai |
|---|---|
| Pool IP klien L2TP | `172.16.66.1-172.16.66.253` |
| Gateway di sisi klien | `172.16.66.253` |
| WG hub (wg0) | `10.10.77.1/24`, port UDP 13231, MTU 1420 |
| WG boost1 / boost2 | `10.250.1.1/30` :51831, `10.250.2.1/30` :51832 |
| WebUI | `0.0.0.0:8443` (API) → `127.0.0.1:8000` (`php -S`) |
| MSS clamp forward | 1360 |

## 5. Efek samping rebuild yang harus dirutinkan
- **Keypair WG server berubah.** Public key baru dicetak `phase_net` / `--status`
  (`wg0 = ...`), lalu tempel ke Mini PC: edit `/etc/wireguard/wg*.conf` bagian
  `PublicKey =` klien, `systemctl restart wg-quick@...`.
- PSK IPsec situs (5 situs) harus tetap `admin123` agar cocok dengan
  `/etc/swanctl/conf.d/l2tp.conf` dan `/etc/ppp/chap-secrets` yang baru.
- GRE/IPIP per situs **belum otomatis** — masih perlu script ppp-up → buat interface GRE
  (tugas #4).

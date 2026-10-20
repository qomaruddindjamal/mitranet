# Analisa Mini PC → VPS Migrasi (2026-10-20)

Sumber: Mini PC `mitranet` 10.10.66.169 (read-only). Target: VPS 103.93.162.168 (belum bisa diakses — lihat bagian Hambatan).

## 1. Profil Mini PC
| Item | Nilai |
|---|---|
| OS | Debian 13 trixie, kernel 6.12.107 amd64 |
| CPU / RAM | Intel Atom x5-Z8350 (4 core) / 3.8 GiB (pakai 604 MiB) |
| Storage | eMMC 57.6 GB, terpakai 2.8 GB (/) |
| Uptime | 6 hari 13 jam |
| IP LAN | 10.10.66.169/24 via DHCP, gw 10.10.66.254 |

## 2. Aplikasi & layanan berjalan
- `mitranet-webui.service` — PHP built-in server `127.0.0.1:8000` (webroot kanonikal `/usr/share/mitranet/web`, 11 MB) **dan** REST API python `0.0.0.0:8443` (`python3 -m mitranet.src.api.server`).
- `xray.service` + `xray@.service` — binary `/usr/local/bin/xray`, config `/usr/local/etc/xray/config.json` (8 KB).
- WireGuard: `wg0` (10.10.77.3/24, ListenPort 51820), `wgboost1`, `wgboost2` (port 51831/51832). Keys di `/etc/wireguard/`.
- `dnsmasq` (listen 127.0.0.1:53), `cron`, `anacron`, `hostapd` (unit ada), `wpa_supplicant`.
- Redis package python3-redis hanya library; `redis-server` inactive.
- `mitranet-vm@.service` — unit KVM/VM ada (`/etc/mitranet/vms`, `kvm_service.json`).
- Paket deb terpasang: `mitranet-core 1.0.2-1~deb13u1`, php8.4-* , wireguard-tools, fping.

## 3. Data yang harus dimigrasikan
| Path | Isi | Ukuran |
|---|---|---|
| `/usr/share/mitranet/web` | WebUI kanonikal | ±11 MB |
| `/etc/mitranet` | konfigurasi (network, qos, vpn_config.json, kvm, wifi, secrets) | 88 KB |
| `/var/lib/mitranet` | state runtime | 36 KB |
| `/var/log/mitranet` | log | 4 KB |
| `/etc/wireguard` | wg0/wgboost1/wgboost2 + keypair | kecil |
| `/usr/local/etc/xray` | config.json xray | 8 KB |
| systemd units | `/etc/systemd/system/mitranet-*`, `xray*` | kecil |
| cron | `crontab -l` (root) | kecil |
| firewall | nftables ruleset 36 baris, iptables 5 baris | kecil |
| user | `citramedia` (uid>=1000), authorized_keys root kosong | — |

Catatan: `/var/www/mitranet` (9.1 MB) adalah salinan webroot gaya pfSense lama — masih ada `/var/www/html`; perlu diputuskan apakah ikut atau memang dihapus (webroot aktif = `/usr/share/mitranet/web`).

## 4. Ketergantungan jaringan ke VPS 103.93.162.168
Sudah ada tunnel Mini PC → VPS:
- WireGuard `wgboost1/2` → endpoint `103.93.162.168:51831/51832` (dan 13231).
- `gre-vps` 10.254.1.2/30, `ipip-vps` 10.253.1.2/30, `eoip-vps` 10.252.1.2/30, `vxlan100` 10.251.1.2/30.
Artinya VPS sudah berperan sebagai hub; migrasi harus menjaga subnet tunnel ini agar tidak tabrakan.

## 5. Temuan KRITIS: "VPS" 103.93.162.168 = MikroTik CHR (bukan Linux)
Akses: `citramedia/K0323205` port 22 (login OK; percobaan password salah sebelumnya memicu staged brute-force list — hindari spam login).
| Item | Nilai |
|---|---|
| OS | RouterOS 7.24.4 stable, board CHR (QEMU), x86_64 |
| Lisensi | level p1 (renewal 2026-11-19) |
| RAM / vCPU | 1 GiB (free 778 MiB) / 1 core 2 GHz |
| Disk | 60 GiB (free 59.6 GiB) |
| User | hanya `citramedia` (group full) |
| Menu `/container` | TIDAK ADA — fitur Container tidak terpasang |

Peran saat ini (hub jaringan CITRA-NETWORK):
- IP publik 103.93.162.168/22 di ether1; gw 10.10.66.254 adalah MikroTik di LAN Mini PC.
- Tunnel masuk: 5x GRE (mtshkm, ppzhq, smkhkm, tbmakmur, tkzhq subnet 10.30.30.0/24), 5x L2TP-in, eoip/ipip/vxlan `*-mitranet` (10.25x.1.1/30 ↔ Mini PC), WG ports 13231–13235 + boost 51831–51834.
- dstnat aktif ke 10.10.66.1 (via tunnel): 80, 5774, 3154, 8000, 8034, 3480, 7333, 8088, 8085, 443, **8443 (API MitraNet)**, 2222→SSH.
- Filter: ssh_blacklist staged (1m/1m/7d), winbox/pptp blacklist serupa, admin_whitelist (103.247.13.9, 10.10.66.0/24, GRE subnet, LAN remote, 114.10.10.109).
- Services: ssh 22, winbox 8291, reverse-proxy 443 aktif; ftp/telnet/www/www-ssl/api/api-ssl disable.

## 6. Hambatan / risiko
1. **RouterOS tidak bisa menjalankan PHP 8.4, xray, mitranet-webui, apt/dpkg** → migrasi penuh aplikasi ke alamat ini mustahil tanpa ganti OS atau fitur Container (yang menabrak batas RAM p1 1 GiB dan perlu paket container).
2. SSH brute-force guard: ≥4 percobaan gagal dalam 1 menit dari satu IP → blacklist 7 hari. Kunci remote bisa hilang bila ceroboh.
3. Semua layanan Mini PC saat ini DIFORWARD lewat CHR ke 10.10.66.1 — klien luar bergantung pada skema ini.

## 7. Opsi yang realistis
- **Opsi A — Mini PC tetap host aplikasi, CHR tetap gateway** (perubahan minim; tambah HA/port-forward).
- **Opsi B — Container Debian di CHR**: instal paket `container` + image Debian 13 (php8.4+xray+mitranet). Risiko: RAM p1 1 GiB sempit, performa WG+tunnel turun, upgrade RouterOS bisa menghapus container.
- **Opsi C — Minta provider ganti image VPS ke Debian 13 / pasang VM Linux**, lalu migrasi penuh + konfigurasi ulang GRE/L2TP/WG dstnat di MikroTik uplink. (Ini "pengganti penuh" yang Anda pilih — butuh aksi provider.)
- Independen dari opsi: cadangkan `/etc/backup_*.rsc` + export RouterOS sebelum perubahan apa pun.

## 8. Usulan urutan migrasi (draf)
1. Dapatkan akses VPS; inventaris OS/CPU/RAM/disk & service yang sudah jalan.
2. Pasang dependensi (php8.4, wireguard-tools, fping) + build/install `mitranet-core` dari sumber kanonikal via `build_packages.sh`.
3. Salin data tabel §3 (rsync/scp), mask secrets; set `MITRANET_API_SECRET` di environment service.
4. Aktifkan unit webui+xray+wg, verifikasi 8000/8443 lokal sebelum buka publik.
5. Migrasi bertahap tunnel WG/GRE/IPIP agar klien tidak putus; uji throughput (Mbps).
6. Mini PC masuk mode legacy/read-only, rollback siap 24–48 jam.

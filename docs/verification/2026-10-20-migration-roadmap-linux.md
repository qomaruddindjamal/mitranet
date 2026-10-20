# Roadmap: MitraNet Mini PC → Linux di 103.93.162.168 (keputusan 2026-10-20: ganti OS VPS)

## Status backup CHR (read-only, tanpa downtime)
- `/export` tersimpan lokal: `backups/mikrotik/CHR-103.93.162.168-export-2026-10-20.rsc` (399 baris, 157 perintah).
- Backup on-device CHR: `prelinux-20261020.backup` (44.6 KiB) + file lama `backup_chr_sebelum_portforward.rsc`, `backup_before_acceptance_audit.rsc`.
- RouterOS 7.24.4, lisensi p1 `90G6/UsnPjD` (renewal 2026-11-19 — perlu konfirmasi provider apakah lisensi tetap aktif setelah ganti OS).

## Kenyataan arsitektur
CHR = pusat kendali 6 situs. Semua dependensi ada di RouterOS ini:
- GRE hub `10.10.66.254` ↔ 5 site (10.10.66.1..5), subnet 10.30.30.0/24 per- site /30.
- L2TP/IPsec server (use-ipsec=yes), PPTP, PPPoE server `PPPOE-CHR` pool 10.10.66.1-253.
- WG RouterOS: peer `mitranet-rinjani` (10.10.77.3 + 10.10.1.0/24, key `subclTBl…`), RB3011 backbone, booster 10.250.1.2 / 10.250.2.2; port 13231–13235, boost 51831–51834.
- EOIP/IPIP/VXLAN `*-mitranet` (10.25x.1.1 ↔ Mini PC) — Stage2.
- 30+ dstnat publik (80/443/8000/8034/5774/3154/3480/7333/8085/8088/8443/2222/8291-3-4/8083-4) → `10.10.66.1` (Mini PC).
- Queue simple, netwatch, romon, scripts (12 scripts di `/system script`).

## Opsi eksekusi
### Opsi 1 — RECOMMENDED: provisioning Linux baru, CHR tetap hidup
1. Minta provider sediakan **VPS Linux kedua** (Debian 13, ≥2 vCPU/2 GiB, IP publik baru) di lokasi/AS yang sama. MitraNet pindah ke sana penuh.
2. Site lama tetap online; rollback utuh; tidak ada downtime 6 situs.
3. RouterOS cukup ditambah static route + dstnat/forward-proxy ke IP baru, atau WG peer baru.
4. Mini PC turun jadi standby/edge LAN.

### Opsi 2 — Reinstall 103.93.162.168 jadi Debian 13 (ganti OS di IP yang sama)
Biaya nyata: **seluruh GRE/L2TP/WG/dstnat 6 situs mati** sampai RouterOS-di sesuatu dipulihkan. MitraNet memang hidup, tapi 6 site kehilangan hub dan semua port forward publik.
Jika tetap dipilih, urutan wajib:
1. Snapshot: export.rsc + `.backup` (sudah) + salin ke Mini PC & laptop.
2. Stage data Mini PC ke staging: `/usr/share/mitranet/web`, `/etc/mitranet`, `/var/lib/mitranet`, `/etc/wireguard`, `/usr/local/etc/xray`, systemd unit, cron, nft/iptables, daftar paket `dpkg -l`.
3. Minta provider reinstall Debian 13 + pastikan konsol/rescue tetap bisa diakses.
4. Bangun ulang lapisan jaringan di Linux: keepalived + GRE/IPsec strongSwan + WG (`wg0/wgboost1/wgboost2`) + dnsmasq + NAT.
5. Restore port forward 8443/80/443 ke IP baru; verifikasi tiap site (uptime GRE, throughput Mbps).
6. Baru matikan layanan di Mini PC setelah semua hijau.

### Opsi 3 — Dual stack sementara (paling aman untuk IP publik yang sama)
Linux di VPS kedua + HAProxy/nginx di IP baru sebagai pengganti dstnat, DNS lama diarahkan bertahap; CHR dipensiunkan setelah 1–2 minggu stabil.

## Checklist sebelum reinstall (jika Opsi 2)
- [ ] Konfirmasi provider: bisa reinstall Debian 13? IPv4 tetap sama? ada rescue console/KVM? lisensi CHR p1 bisa dipindah ke CHR lain?
- [ ] Siapkan akun cadangan + authorized_keys SSH di target (jangan cuma password).
- [ ] Backup `.backup` ke luar CHR (FTP/api sedang disable — perlu Winbox/manual).
- [ ] Catat 6 site remote IP GRE & WG peer public key/endpoint (sudah di export.rsc).
- [ ] Jadwal window: semua site offline sementara — perlu persetujuan Anda.

## Yang TIDAK bisa dilakukan
RouterOS tidak dapat menjalankan PHP 8.4/xray/mitranet-core; fitur `/container` tidak terpasang dan lisensi p1 dibatasi 1 GiB RAM, jadi menjalankan WebUI MitraNet di CHR (termasuk lewat container) tidak layak untuk produksi.

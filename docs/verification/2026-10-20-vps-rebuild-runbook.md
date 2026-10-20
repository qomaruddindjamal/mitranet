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
python tools/ops/vps_rebuild_deploy.py --wait 900 --phases deps,app
python tools/ops/vps_rebuild_deploy.py --status
python tools/ops/vps_rebuild_deploy.py --phases net      # IPsec MATI (default) -> L2TP UDP polos
python tools/ops/vps_rebuild_deploy.py --phases fw --confirm-firewall
# kalau nanti IPsec memang dibutuhkan: tambahkan --with-ipsec (dengan net-watchdog aktif)
```
`tools/ops/vps-bootstrap.sh` = sisi VPS (idempotent, phase `deps|app|net|fw|status`).

**Profil aman = default (`WITH_IPSEC=0`):** strongSwan tidak dipasang, unit-nya di-mask,
rule UDP 500/4500 dan `ip protocol esp` tidak dibuat. 5 situs disetel `use-ipsec=no`
 pada `/interface l2tp-client`-nya; autentikasi tetap CHAP + PSK file, tapi trafik L2TP
tidak terenkripsi IPsec — untuk jalur yang butuh kerahasiaan, pakai WireGuard.

**Urutan wajib:** `deps,app` dulu dan verifikasi 8443 hidup; `net` **jangan** digabung
dengan yang lain; `fw` paling akhir dan butuh `--confirm-firewall`.

## 1b. Temuan penting (rebuild ke-2, 2026-10-20/21)
- Fase `net` (saat strongSwan di-restart) membuat box hilang total; outage pertama juga
  terjadi setelah `swanctl --load-all`.
- Tersangka konfigurasi: `start_action = trap` tanpa traffic selector → strongSwan memasang
  route 0.0.0.0/0 ke SA yang tidak pernah terbentuk. Sudah diganti `start_action = default`
  + `local_ts <pub_ip>/32` + tripwire default-route + `net-watchdog` 180 s.
- **Tapi restart dari panel TIDAK memulihkan** (state IPsec seharusnya hilang saat reboot), dan
  `tracert` mati setelah hop 6 (`137.59.127.70`). Jadi ada kemungkinan kedua: **IP kena
  mitigation/null-route di sisi provider** karena trafik ESP/IKE abnormal. Teks tiket:
  `docs/verification/2026-10-21-provider-ticket.md`.
- Konsekuensi untuk rebuild ke-3: IPsec dipasang paling akhir dan tanpa trap; kalau box hilang
  lagi **tepat setelah `net`**, itu hampir pasti provider yang memblok, bukan OS → buka tiket,
  jangan rebuild lagi.

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

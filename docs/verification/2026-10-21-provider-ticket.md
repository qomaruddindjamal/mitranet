# Tiket provider — IP 103.93.162.168 tidak terjangkau (2026-10-21)

## Gejala
- Sejak 2026-10-20 23:37 WIB, IP `103.93.162.168` tidak menjawab **apa pun**: ICMP, TCP 22, TCP 8443, UDP 13231.
- Tes dari dua jalur internet berbeda (kantor + Mini PC) sama: 100% packet loss.
- `tracert` dari laptop berhenti setelah hop 6 (`137.59.127.70`), semua hop sesudahnya `*`.
- Tombol **restart** dari panel sudah dicoba, tidak mengubah apa pun.
- Panel "Open Console" tidak bisa dibuka (error), jadi tidak ada VNC/serial.

## Yang perlu dicek provider
1. Apakah VM benar-benar berstatus **Running** dan boot-nya selesai (graph CPU/RAM bergerak, bukan datar)?
2. Apakah IP terkena **DDoS mitigation / null-route / blocklist** otomatis? Jika ya, mohon dilepas dan kabari saya.
3. Apakah ada **security group / firewall di sisi panel** yang aktif dan memblokir inbound?
4. Mohon sediakan **VNC / serial console** lewat tiket ini (tombol di panel error).
5. Kalau VM ternyata corrupt di sisi host, silakan **migrate ke host lain** — saya siap reinstall.

## Catatan penting
Ini bukan salah konfigurasi yang butuh login: saya butuh akses konsol atau pelepasan block.
Begitu SSH hidup lagi, semua yang saya pasang (PHP/WebUI 8443, WireGuard, L2TP/IPsec, xray)
sudah tersimpan di disk dan pulih dengan satu perintah.

## Varian English (bila tiket diarahkan ke upstream)
IP 103.93.162.168 is completely unreachable since 2026-10-20 23:37 UTC+7 — no ICMP, no TCP 22/8443,
no UDP. Verified from two independent networks. traceroute dies past hop 6 (137.59.127.70).
A panel restart did not help and the "Open Console"/VNC button errors out. Please confirm the VM
is actually running/booted, check whether the IP is under DDoS mitigation or a null-route/block,
and provide console access or release the block.

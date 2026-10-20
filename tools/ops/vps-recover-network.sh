#!/bin/bash
# Pemulihan jaringan VPS MitraNet (103.93.162.168) bila SSH tidak bisa diakses.
# Jalankan dari KONSOL/VNC provider (bukan SSH), sebagai root.
#
#   bash vps-recover-network.sh            # pulihkan konektivitas + diagnosis
#   bash vps-recover-network.sh --wg       # juga nyalakan kembali interface WireGuard
#
# Aman untuk dijalankan berulang kali (idempotent).
set +e

FLUSH_ONLY=0
[ "$1" = "--wg" ] && FLUSH_ONLY=2

echo "=== 1. Buka kembali semua firewall (flush = policy default accept) ==="
nft flush ruleset
iptables -P INPUT ACCEPT;  iptables -P FORWARD ACCEPT;  iptables -P OUTPUT ACCEPT
ip6tables -P INPUT ACCEPT; ip6tables -P FORWARD ACCEPT; ip6tables -P OUTPUT ACCEPT
echo "ruleset lines setelah flush: $(nft list ruleset | wc -l)"

echo "=== 2. Hentikan proses yang mungkin menggantung ==="
# systemd-run/`swanctl --load-all` yang menggantung bisa menahan job queue
systemctl reset-failed 2>/dev/null
pkill -f 'swanctl --load-all' 2>/dev/null
for u in mitranet-fw-rollback mitranet-fw-rollback2 fw-rb; do
  systemctl stop $u.timer $u.service 2>/dev/null
done

echo "=== 3. Restart daemon jaringan/VPN satu per satu dengan timeout ==="
for svc in strongswan xl2tpd; do
  echo "-- $svc"
  timeout 25 systemctl restart $svc
  echo "   status: $(systemctl is-active $svc)"
done
timeout 20 systemctl restart ssh sshd 2>/dev/null
echo "   sshd: $(systemctl is-active sshd ssh 2>/dev/null | tr '\n' ' ')"

echo "=== 4. Verifikasi alamat & route ==="
ip -br addr show eth0
ip -4 route show default
ss -lun | grep -E ':500|:4500|:1701' || echo "   (UDP IPsec/L2TP belum listen)"
ss -tln | grep -E ':22|:8443' || echo "   (TCP 22/8443 belum listen)"

echo "=== 5. Interface WireGuard ==="
if [ "$FLUSH_ONLY" = "2" ]; then
  timeout 25 systemctl restart wg-quick@wg0 wg-quick@wgboost1 wg-quick@wgboost2
else
  echo "   dilewati (pakai --wg untuk menyalakan wg0/wgboost1/wgboost2)"
fi
ip -br addr show 2>/dev/null | grep -E '^wg' || true

echo "=== 6. Uji keluar ==="
timeout 10 curl -s -o /dev/null -w "   https://deb.debian.org -> %{http_code}\n" https://deb.debian.org || echo "   CURL_FAIL (cek gateway/provider)"
ping -c2 -W2 103.93.163.254 >/dev/null 2>&1 && echo "   gateway LAN OK" || echo "   gateway LAN tidak merespons ICMP (belum tentu fatal)"

echo "=== 7. 20 baris log terakhir yang relevan ==="
journalctl -n 60 --no-pager 2>/dev/null | grep -iE 'charon|xl2tpd|nft|sshd|eth0|kernel' | tail -20

cat <<'NOTE'

=== Catatan sesudah pulih ===
1. Jangan langsung apply /etc/nftables.conf. Cek dulu:  nft -c -f /etc/nftables.conf
2. Kalau ruleset mau dipasang lagi, selalu pasang watchdog anti-lockout:
     systemd-run --on-active=240s --unit=fw-rb /usr/sbin/nft flush ruleset
     nft -f /etc/nftables.conf
   lalu batalkan setelah SSH sesi baru terbukti masuk:
     systemctl stop fw-rb.timer
3. strongSwan di Debian 13 hanya membaca /etc/swanctl/conf.d/*.conf (bukan
   /etc/ipsec.conf). File /etc/ipsec.conf & /etc/ipsec.secrets di host ini tidak
   dipakai - hapus agar tidak menyesatkan.
4. Setelah VPS sehat, nyalakan lagi tunnel di Mini PC:
     systemctl enable --now wg-quick@wg0 wg-quick@wgboost1 wg-quick@wgboost2
NOTE

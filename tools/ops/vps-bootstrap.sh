#!/bin/bash
# vps-bootstrap.sh — rebuild MitraNet hub di Debian 13 (idempotent, phase-gated)
#
# Aturan anti-lockout (penyebab outage 2026-10-20):
#   1. Fase 'fw' SELALU terakhir dan harus dipanggil eksplisit (tidak ikut 'all').
#   2. Ruleset diverifikasi `nft -c` dulu, dipasang dengan watchdog flush, dan
#      TIDAK di-enable di boot (systemctl disable nftables) -> reboot selalu pulih.
#   3. Baris pertama chain input: lo, established/related, dport 22 accept.
#   4. Semua restart service dibungkus `timeout` supaya tidak bisa menggantung.
#
# Pemakaian (di VPS, sebagai root):
#   PUB_IP=1.2.3.4 L2TP_PSK=xxx bash vps-bootstrap.sh deps
#   ... app | net | fw | status
set -uo pipefail

PHASE="${1:-all}"
LAN_IF="${LAN_IF:-eth0}"
PUB_IP="${PUB_IP:?Set PUB_IP (IP publik VPS)}"
L2TP_PSK="${L2TP_PSK:?Set L2TP_PSK}"
CLIENT_POOL="${CLIENT_POOL:-172.16.66.1-172.16.66.253}"
GW_IP="${GW_IP:-172.16.66.253}"
CLIENT_NET="${CLIENT_NET:-172.16.66.0/24}"
WG_NET="${WG_NET:-10.10.77.0/24}"
PAYLOAD_DIR="${PAYLOAD_DIR:-/tmp/payload}"
# PROFIL AMAN (default): TANPA IPsec. strongSwan 6 dengan child transport pernah
# membuat box hilang total 2x. Set WITH_IPSEC=1 hanya setelah provider memastikan
# tidak ada block, dan selalu lewat net-watchdog.
WITH_IPSEC="${WITH_IPSEC:-0}"

step() { echo "==> $*"; }
tmo() { timeout 90 "$@" || echo "   WARN: $* (rc=$?)"; }
# watchdog transien: self-heal kalau perubahan jaringan membuat box tak terjangkau
arm_watchdog() { systemd-run --on-active="${2}s" --unit="$1" /bin/bash -c "$3" >/dev/null 2>&1 \
                  && step "watchdog $1 aktif ${2}s" || echo "   WARN: watchdog $1 gagal dipasang"; }
disarm_watchdog() { systemctl cancel "$1.timer" >/dev/null 2>&1 || true
                    systemctl stop "$1.timer" "$1.service" >/dev/null 2>&1 || true
                    echo "   watchdog $1 dibatalkan"; }

phase_deps() {
  export DEBIAN_FRONTEND=noninteractive
  PKGS="php8.4-cli php8.4-curl php8.4-mbstring php8.4-sqlite3 wireguard-tools \
        xl2tpd ppp nftables fping curl python3-pip \
        python3-pydantic python3-yaml python3-requests python3-netifaces \
        iproute2 iptables jq"
  if [ "$WITH_IPSEC" = "1" ]; then
    PKGS="$PKGS strongswan strongswan-swanctl libcharon-extra-plugins"
  fi
  apt-get update -y
  # shellcheck disable=SC2086
  apt-get install -y --no-install-recommends $PKGS
  step "versi: $(php -v | head -1)"
  if [ "$WITH_IPSEC" = "1" ]; then
    swanctl --version 2>/dev/null | head -1 || true
  else
    step "profil TANPA IPsec: strongSwan tidak dipasang/di-mask"
    systemctl disable --now strongswan strongswan-swanctl >/dev/null 2>&1 || true
    systemctl mask strongswan strongswan-swanctl >/dev/null 2>&1 || true
    rm -f /etc/swanctl/conf.d/l2tp.conf
  fi
  systemctl is-enabled ssh >/dev/null 2>&1 && step "sshd enabled OK"
}

phase_app() {
  [ -f "$PAYLOAD_DIR/minipc-staging.tar.gz" ] || { echo "ERROR: $PAYLOAD_DIR/minipc-staging.tar.gz belum di-upload"; exit 1; }
  STAGE=/opt/mitranet-stage
  step "extract payload -> $STAGE"
  rm -rf "$STAGE"; mkdir -p "$STAGE"
  tar -xzf "$PAYLOAD_DIR/minipc-staging.tar.gz" -C "$STAGE"
  # hanya path aplikasi; etc/network, etc/wireguard, etc/systemd, var/www TIDAK disentuh
  mkdir -p /usr/share /etc/mitranet /var/lib/mitranet /usr/local
  cp -a "$STAGE/usr/share/mitranet/." /usr/share/mitranet/
  [ -d "$STAGE/etc/mitranet" ]  && cp -a "$STAGE/etc/mitranet/."  /etc/mitranet/
  [ -d "$STAGE/var/lib/mitranet" ] && cp -a "$STAGE/var/lib/mitranet/." /var/lib/mitranet/
  [ -d "$STAGE/usr/local" ]     && cp -a "$STAGE/usr/local/."     /usr/local/
  # /usr/share/mitranet/mitranet di Mini PC cuma symlink ke /mitranet (repo). Di VPS
  # symlink itu menggantung -> ModuleNotFoundError. Pasang repo sebagai /opt/mitranet.
  step "paket python 'mitranet' -> /opt/mitranet"
  if [ -f "$PAYLOAD_DIR/mitranet-src.tar.gz" ]; then
    rm -rf /opt/mitranet
    tar -xzf "$PAYLOAD_DIR/mitranet-src.tar.gz" -C /opt
    ln -sfn /opt/mitranet /usr/share/mitranet/mitranet
    find /opt/mitranet -name '__pycache__' -type d -prune -exec rm -rf {} + 2>/dev/null || true
    ls -l /usr/share/mitranet/mitranet | sed 's/.* -> /symlink -> /'
    test -f /opt/mitranet/src/api/server.py \
      && step "src/api/server.py OK" || step "ERROR: src/api/server.py tidak ada"
  else
    step "ERROR: $PAYLOAD_DIR/mitranet-src.tar.gz belum di-upload"
    exit 1
  fi
  step "webroot: $(ls /usr/share/mitranet/web/index.php 2>&1) | pkg: $(ls -d /usr/share/mitranet/mitranet 2>&1)"
  if [ -f "$PAYLOAD_DIR/xray-bin" ]; then
    install -m 0755 "$PAYLOAD_DIR/xray-bin" /usr/local/bin/xray
    mkdir -p /usr/local/etc/xray
    step "xray: $(/usr/local/bin/xray version 2>&1 | head -1)"
  fi
  step "env service"
  [ -f /etc/mitranet/webui.env ] || {
    umask 077
    { echo "MITRANET_API_SECRET=$(openssl rand -hex 24)"; echo "PYTHONUNBUFFERED=1"; } > /etc/mitranet/webui.env
  }
  cat >/etc/systemd/system/mitranet-webui.service <<'UNIT'
[Unit]
Description=MitraNet WebUI + REST API
After=network-online.target
[Service]
Type=simple
User=root
WorkingDirectory=/usr/share/mitranet
EnvironmentFile=/etc/mitranet/webui.env
ExecStart=/usr/bin/python3 -m mitranet.src.api.server
Restart=always
RestartSec=3
KillMode=process
PrivateTmp=true
ReadWritePaths=/etc/mitranet /var/lib/mitranet /var/log /run /tmp
[Install]
WantedBy=multi-user.target
UNIT
  [ -f /usr/local/etc/xray/config.json ] || echo '{}' > /usr/local/etc/xray/config.json
  cat >/etc/systemd/system/xray.service <<'UNIT'
[Unit]
Description=Xray Core
After=network-online.target
[Service]
Type=simple
ExecStart=/usr/local/bin/xray run -config /usr/local/etc/xray/config.json
Restart=on-failure
[Install]
WantedBy=multi-user.target
UNIT
  systemctl daemon-reload
  systemctl enable mitranet-webui >/dev/null 2>&1
  tmo systemctl restart mitranet-webui
  sleep 4
  systemctl is-active mitranet-webui || journalctl -u mitranet-webui -n 25 --no-pager
  step "listen: $(ss -Hlnt | grep -E ':8000|:8443' | tr -s ' ' | cut -d' ' -f2 | paste -sd, -)"
}

phase_net() {
  arm_watchdog net-watchdog 180 "systemctl stop strongswan xl2tpd wg-quick@wg0; nft flush ruleset; ip route flush cache"
  step "sysctl forward"
  echo -e "net.ipv4.ip_forward=1\nnet.ipv4.conf.all.rp_filter=0" >/etc/sysctl.d/99-mitranet-forward.conf
  sysctl -p /etc/sysctl.d/99-mitranet-forward.conf >/dev/null

  step "wireguard (keypair baru tiap rebuild)"
  mkdir -p /etc/wireguard; chmod 700 /etc/wireguard
  for t in wg0 wgboost1 wgboost2; do
    [ -f "/etc/wireguard/$t.key" ] || wg genkey >"/etc/wireguard/$t.key"
    chmod 600 "/etc/wireguard/$t.key"
  done
  wg0_priv=$(cat /etc/wireguard/wg0.key)
  wg0_pub=$(wg pubkey </etc/wireguard/wg0.key)
  b1_priv=$(cat /etc/wireguard/wgboost1.key); b1_pub=$(wg pubkey </etc/wireguard/wgboost1.key)
  b2_priv=$(cat /etc/wireguard/wgboost2.key); b2_pub=$(wg pubkey </etc/wireguard/wgboost2.key)
  cat >/etc/wireguard/wg0.conf <<CONF
[Interface]
Address = 10.10.77.1/24
ListenPort = 13231
PrivateKey = $wg0_priv
MTU = 1420
# Tambah peer dengan: bash $0 peer wg0 <publickey> <allowedips>
# (wg setconf menolak blok [Peer] tanpa PublicKey, jadi jangan tulis [Peer] kosong di sini)
CONF
  cat >/etc/wireguard/wgboost1.conf <<CONF
[Interface]
Address = 10.250.1.1/30
ListenPort = 51831
PrivateKey = $b1_priv
MTU = 1420
CONF
  cat >/etc/wireguard/wgboost2.conf <<CONF
[Interface]
Address = 10.250.2.1/30
ListenPort = 51832
PrivateKey = $b2_priv
MTU = 1420
CONF
  step "public keys VPS (untuk config client):"
  echo "   wg0       = $wg0_pub"
  echo "   wgboost1  = $b1_pub"
  echo "   wgboost2  = $b2_pub"
  tmo systemctl restart wg-quick@wg0

  step "xl2tpd LNS pool=$CLIENT_POOL gw=$GW_IP"
  cat >/etc/xl2tpd/xl2tpd.conf <<CONF
[global]
listen-addr = $PUB_IP
port = 1701
ipsec saref = no
auth file = /etc/ppp/chap-secrets
[lns default]
ip range = $CLIENT_POOL
local ip = $GW_IP
require chap = yes
refuse pap = yes
require authentication = yes
name = LNS
pppoptfile = /etc/ppp/options.xl2tpd
length bit = yes
CONF
  cat >/etc/ppp/chap-secrets <<CONF
# client       server  secret      IP addresses (local,remote)
smkhkm         *       $L2TP_PSK   $GW_IP,172.16.66.1
ppzhq          *       $L2TP_PSK   $GW_IP,172.16.66.2
tkzhq          *       $L2TP_PSK   $GW_IP,172.16.66.3
mtshkm         *       $L2TP_PSK   $GW_IP,172.16.66.4
tbmakmur       *       $L2TP_PSK   $GW_IP,172.16.66.5
citramedia     *       $L2TP_PSK   $GW_IP,172.16.66.250
CONF
  chmod 600 /etc/ppp/chap-secrets
  cat >/etc/ppp/options.xl2tpd <<'CONF'
ipcp-accept-local
ipcp-accept-remote
ms-dns 1.1.1.1
ms-dns 8.8.8.8
noccp
auth
mtu 1400
mru 1400
lcp-echo-failure 5
lcp-echo-interval 10
proxyarp
connect-delay 5000
CONF
  chmod 600 /etc/ppp/options.xl2tpd
  pkill -f '[x]l2tpd -d' 2>/dev/null || true
  pkill -x xl2tpd 2>/dev/null || true
  sleep 1
  tmo systemctl restart xl2tpd

  if [ "$WITH_IPSEC" != "1" ]; then
    step "IPsec DIMATIKAN (profil aman) -> L2TP jalan atas UDP polos 1701"
    systemctl disable --now strongswan strongswan-swanctl >/dev/null 2>&1 || true
    rm -f /etc/ipsec.conf /etc/ipsec.secrets /etc/swanctl/conf.d/l2tp.conf
    step "verifikasi: strongswan=$(systemctl is-active strongswan 2>&1) xl2tpd=$(systemctl is-active xl2tpd 2>&1) udp1701=$(ss -Hlnu | grep -c ':1701')"
    step "default route: $(ip -4 route show default | head -1)"
    phase_nat
    disarm_watchdog net-watchdog || true
    return 0
  fi

  step "strongSwan (swanctl, Debian 13 tidak pakai ipsec.conf)"
  # PENTING: start_action=trap dengan remote_ts 0/0 memasang route/acquire untuk SEMUA
  # trafik -> box hilang dari jaringan (ini penyebab outage 2x). Pakai 'default'.
  rm -f /etc/ipsec.conf /etc/ipsec.secrets
  mkdir -p /etc/swanctl/conf.d
  cat >/etc/swanctl/conf.d/l2tp.conf <<CONF
connections {
  l2tp {
    version = 1
    local_addrs = $PUB_IP
    proposals = aes128-sha1-modp1024, aes128-sha256-modp2048, 3des-sha1-modp1024
    local {
      auth = psk
      id = $PUB_IP
    }
    remote {
      auth = psk
      id = %any
    }
    children {
      l2tp {
        mode = transport
        local_ts = $PUB_IP/32
        remote_ts = 0.0.0.0/0
        dpd_action = clear
        start_action = default
        close_action = none
        updown = none
      }
    }
  }
}
secrets {
  ike-l2tp { secret = "$L2TP_PSK" }
}
CONF
  tmo systemctl restart strongswan
  sleep 2
  timeout 25 swanctl --load-all || step "WARN swanctl load"
  sleep 2
  if ip -4 route show | grep -qE '^(default|0\.0\.0\.0/0) .* (ipsec|xfrm|newnexthop)' \
     || ip -4 route show | grep -c '^0\.0\.0\.0/0' | grep -qv '^1$'; then
    step "BAHAYA: route global berubah -> strongSwan/xl2tpd langsung dihentikan"
    systemctl stop strongswan xl2tpd
  fi
  step "verifikasi net: default=$(ip -4 route show default | head -1)"
  timeout 20 swanctl --list-conns | head -5 || true
  phase_nat
  disarm_watchdog net-watchdog || true
}

phase_fw() {
  step "firewall (opt-in, watchdog 120s, TIDAK dipersist ke boot)"
  if [ "$WITH_IPSEC" = "1" ]; then
    IPS_RULES="    udp dport { 500, 4500 } accept
    ip protocol esp accept"
    L2TP_PORTS="1701"
  else
    IPS_RULES=""
    L2TP_PORTS="1701"
  fi
  cat >/etc/nftables.conf <<CONF
#!/usr/sbin/nft -f
flush ruleset
table inet filter {
  set tunnel_nets {
    type ipv4_addr
    flags interval
    elements = { $CLIENT_NET, $WG_NET, 10.250.1.0/30, 10.250.2.0/30 }
  }
  chain input {
    type filter hook input priority 0; policy drop;
    iif lo accept
    ct state established,related accept
    tcp dport 22 ct state new limit rate 6/minute accept
    ct state invalid drop
    icmp type echo-request limit rate 10/second accept
    udp dport { 13231-13235, 51831-51834 } accept
    tcp dport { 8000, 8443 } accept
    udp dport { $L2TP_PORTS } accept
$IPS_RULES
    ip protocol gre accept
    ip protocol 4 accept
  }
  chain forward {
    type filter hook forward priority 0; policy drop;
    ct state established,related accept
    tcp flags & (syn | rst) == syn tcp option maxseg size set 1360
    ip saddr @tunnel_nets oifname $LAN_IF accept
    iifname $LAN_IF ip daddr @tunnel_nets accept
  }
  chain postrouting {
    type nat hook postrouting priority 100; policy accept;
    oifname $LAN_IF masquerade
  }
}
CONF
  if ! nft -c -f /etc/nftables.conf; then
    step "compile GAGAL -> ruleset TIDAK dipasang (akses SSH tetap aman)"
    exit 1
  fi
  systemd-run --on-active=120s --unit=fw-watchdog /usr/sbin/nft flush ruleset >/dev/null 2>&1 || true
  step "watchdog fw-watchdog aktif 120s"
  nft -f /etc/nftables.conf && step "NFT_APPLIED"
  systemctl disable nftables >/dev/null 2>&1 || true
  step "cek lokal: sshd=$(ss -Hlnt 2>/dev/null | grep -c ':22 ') 8443=$(ss -Hlnt 2>/dev/null | grep -c ':8443 ')"
  echo "   Setelah SSH dari luar terkonfirmasi hidup: systemctl cancel fw-watchdog.timer"
  echo "   Jika tidak, watchdog flush ruleset sendiri dalam 120 detik."
}

phase_nat() {
  step "NAT + MSS clamp untuk tunnel (runtime, tanpa aturan input -> tak bisa lockout)"
  TUNNEL_SET="{ $CLIENT_NET, $WG_NET, 10.250.1.0/30, 10.250.2.0/30 }"
  nft add table inet mitranet_nat 2>/dev/null || true
  nft add chain inet mitranet_nat postrouting '{ type nat hook postrouting priority 100; policy accept; }' 2>/dev/null || true
  nft flush chain inet mitranet_nat postrouting
  nft add rule inet mitranet_nat postrouting oifname "$LAN_IF" ip saddr "$TUNNEL_SET" masquerade
  nft add chain inet mitranet_nat forward '{ type filter hook forward priority 0; policy accept; }' 2>/dev/null || true
  nft flush chain inet mitranet_nat forward
  nft add rule inet mitranet_nat forward oifname "$LAN_IF" "tcp flags & (syn | rst) == syn tcp option maxseg size set 1360"
  nft add rule inet mitranet_nat forward iifname "$LAN_IF" "ip daddr $TUNNEL_SET tcp flags & (syn | rst) == syn tcp option maxseg size set 1360"
  step "masquerade aktif: $(nft list chain inet mitranet_nat postrouting | grep -c masquerade) rule"
}

phase_peer() {
  tun="${2:?usage: peer <tunnel> <client-pubkey> [allowedips]}"
  pub="${3:?usage: peer <tunnel> <client-pubkey> [allowedips]}"
  aips="${4:-}"
  conf="/etc/wireguard/$tun.conf"
  [ -f "$conf" ] || { echo "ERROR: $conf tidak ada"; exit 1; }
  if [ -z "$aips" ]; then
    case "$tun" in
      wg0) aips="10.10.77.0/24" ;;
      wgboost1) aips="10.250.1.2/32" ;;
      wgboost2) aips="10.250.2.2/32" ;;
      *) aips="0.0.0.0/0" ;;
    esac
  fi
  grep -q "^PublicKey = $pub" "$conf" && { step "peer sudah ada di $conf"; return 0; }
  # wg-quick menolak blok [Peer] tanpa PublicKey: buang sisa file setelah [Interface]
  awk '/^\[Peer\]/{stop=1} !stop{print}' "$conf" > "$conf.tmp" && mv "$conf.tmp" "$conf"
  {
    echo ""
    echo "[Peer]"
    echo "PublicKey = $pub"
    echo "AllowedIPs = $aips"
    echo "PersistentKeepalive = 25"
  } >> "$conf"
  step "peer ditambahkan ke $tun: $pub ($aips)"
  tmo systemctl restart "wg-quick@$tun"
  sleep 3
  wg show "$tun" 2>&1 | head -8
}

phase_status() {
  echo "--- addr/route ---"; ip -4 addr show dev "$LAN_IF" | grep inet; ip route | head -5
  echo "--- listen ---"; ss -Hlnu 2>/dev/null | grep -E '1701|1323|5183|4500|500' | head -8
  ss -Hlnt 2>/dev/null | grep -E ':22|:8000|:8443' | head -5
  echo "--- services ---"
  for s in ssh mitranet-webui xray xl2tpd strongswan wg-quick@wg0; do
    printf '%-22s %s\n' "$s" "$(systemctl is-active $s 2>&1)"
  done
  echo "--- wg ---"; wg show 2>/dev/null | head -8
  echo "--- pool ---"; grep -E 'ip range|local ip' /etc/xl2tpd/xl2tpd.conf
  echo "--- firewall ---"; nft list ruleset 2>/dev/null | head -6
  echo "--- ipsec profil ---"; echo "WITH_IPSEC=$WITH_IPSEC; strongswan=$(systemctl is-active strongswan 2>&1) masked=$(systemctl is-enabled strongswan 2>&1)"
  echo "PUBLIC WG KEYS:"; for t in wg0 wgboost1 wgboost2; do [ -f /etc/wireguard/$t.key ] && echo "  $t = $(wg pubkey </etc/wireguard/$t.key)"; done
}

case "$PHASE" in
  deps) phase_deps ;;
  app) phase_app ;;
  net) phase_net ;;
  nat) phase_nat ;;
  fw) phase_fw ;;
  peer) phase_peer "$@" ;;
  status) phase_status ;;
  all) phase_deps; phase_app; phase_net; step "selesai — jalankan fase 'fw' manual setelah SSH terverifikasi" ;;
  *) echo "phase: deps|app|net|fw|status|all"; exit 1 ;;
esac
step "PHASE $PHASE selesai"

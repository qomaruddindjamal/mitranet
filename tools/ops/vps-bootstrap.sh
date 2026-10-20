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
  apt-get update -y
  apt-get install -y --no-install-recommends \
    php8.4-cli php8.4-curl php8.4-mbstring php8.4-sqlite3 \
    wireguard-tools strongswan strongswan-swanctl libcharon-extra-plugins \
    xl2tpd ppp nftables fping curl python3-pip \
    python3-pydantic python3-yaml python3-requests python3-netifaces \
    iproute2 iptables jq
  step "versi"
  php -v | head -1; swanctl --version 2>/dev/null | head -1 || true
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

[Peer]
# Mini PC diisi manual setelah rebuild (publik key client berubah)
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

[lns default]
ip range = $CLIENT_POOL
local ip = $GW_IP
require chap = yes
refuse-pap = no
require-mschap-v2 = no
authentication = required
name = LNS
mode = server
length-lookup = no
ipsec saref = no
max send window = 320
max receive window = 320
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
ms-dns=1.1.1.1 8.8.8.8
noccp
auth
mtu 1400
mru 1400
lcp-echo-failure 5
lcp-echo-interval 10
proxyarp
conf-file /dev/null
CONF
  tmo systemctl restart xl2tpd

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
  disarm_watchdog net-watchdog || true
}

phase_fw() {
  step "firewall (opt-in, watchdog 120s, TIDAK dipersist ke boot)"
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
    udp dport { 1701, 500, 4500 } accept
    ip protocol esp accept
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
  echo "PUBLIC WG KEYS:"; for t in wg0 wgboost1 wgboost2; do [ -f /etc/wireguard/$t.key ] && echo "  $t = $(wg pubkey </etc/wireguard/$t.key)"; done
}

case "$PHASE" in
  deps) phase_deps ;;
  app) phase_app ;;
  net) phase_net ;;
  fw) phase_fw ;;
  status) phase_status ;;
  all) phase_deps; phase_app; phase_net; step "selesai — jalankan fase 'fw' manual setelah SSH terverifikasi" ;;
  *) echo "phase: deps|app|net|fw|status|all"; exit 1 ;;
esac
step "PHASE $PHASE selesai"

#!/bin/bash
# MitraNet - Feature Injection Script (V2Ray/Xray, CLI, Web API, pf Firewall)
set -e

TARGET_DIR="${1:-bulid/iso_root}"

echo "=== [MitraNet] Injecting Features into Rootfs ==="
echo "[*] Target Rootfs: ${TARGET_DIR}"

if [ ! -d "${TARGET_DIR}/etc" ]; then
    echo "[-] Error: ${TARGET_DIR} does not appear to be an unpacked root filesystem."
    echo "    Run 'bash bulid/unpack_iso.sh' first."
    exit 1
fi

# 1. Create necessary destination directories
mkdir -p "${TARGET_DIR}/usr/local/bin"
mkdir -p "${TARGET_DIR}/usr/local/etc/rc.d"
mkdir -p "${TARGET_DIR}/usr/local/etc/xray/nodes"
mkdir -p "${TARGET_DIR}/usr/local/share/xray"
mkdir -p "${TARGET_DIR}/var/log/xray"

# 2. Inject Xray binary (FreeBSD amd64)
if [ -f "programs/xray/bin/xray" ]; then
    echo "[*] Injecting FreeBSD Xray binary..."
    cp -f "programs/xray/bin/xray" "${TARGET_DIR}/usr/local/bin/xray"
    chmod +x "${TARGET_DIR}/usr/local/bin/xray"
    [ -f "programs/xray/bin/geoip.dat" ] && cp -f "programs/xray/bin/"*.dat "${TARGET_DIR}/usr/local/share/xray/" || true
else
    echo "[!] Warning: programs/xray/bin/xray not found. Placeholder or downloading..."
    bash programs/xray/bin/fetch_xray_freebsd.sh || true
    if [ -f "programs/xray/bin/xray" ]; then
        cp -f "programs/xray/bin/xray" "${TARGET_DIR}/usr/local/bin/xray"
        chmod +x "${TARGET_DIR}/usr/local/bin/xray"
    fi
fi

# 3. Inject Xray Config Templates
echo "[*] Injecting Xray configuration templates..."
cp -f programs/xray/config/*.json "${TARGET_DIR}/usr/local/etc/xray/"
# Set VLESS Reality as default initial active config
cp -f programs/xray/config/config_vless_reality.json "${TARGET_DIR}/usr/local/etc/xray/config.json"
# Populate node store
cp -f programs/xray/config/config_vless_reality.json "${TARGET_DIR}/usr/local/etc/xray/nodes/vless_reality_default.json"
cp -f programs/xray/config/config_vless_ws.json "${TARGET_DIR}/usr/local/etc/xray/nodes/vless_ws_default.json"
cp -f programs/xray/config/config_vmess_ws.json "${TARGET_DIR}/usr/local/etc/xray/nodes/vmess_ws_default.json"

# 4. Inject FreeBSD rc.d startup script
echo "[*] Injecting rc.d service script..."
cp -f programs/xray/service/rc.d_xray "${TARGET_DIR}/usr/local/etc/rc.d/xray"
chmod +x "${TARGET_DIR}/usr/local/etc/rc.d/xray"

# 5. Inject pf firewall transparent proxy rules
echo "[*] Injecting pf firewall rules..."
cp -f programs/xray/service/pf_xray.conf "${TARGET_DIR}/usr/local/etc/xray/pf_xray.conf"

# 6. Inject CLI Management Tools
echo "[*] Injecting MitraNet CLI tools..."
cp -f programs/xray/cli/mitranet-cli "${TARGET_DIR}/usr/local/bin/mitranet-cli"
cp -f programs/xray/cli/mitra-v2ray "${TARGET_DIR}/usr/local/bin/mitra-v2ray"
chmod +x "${TARGET_DIR}/usr/local/bin/mitranet-cli" "${TARGET_DIR}/usr/local/bin/mitra-v2ray"

# 7. Inject Web API Daemon
echo "[*] Injecting Web API daemon..."
cp -f programs/xray/web/mitranet_api.py "${TARGET_DIR}/usr/local/bin/mitranet-web"
chmod +x "${TARGET_DIR}/usr/local/bin/mitranet-web"

# 8. Hook into /etc/rc.local to auto-start MitraNet services
echo "[*] Updating /etc/rc.local with MitraNet auto-launch..."
if ! grep -q "mitranet-web" "${TARGET_DIR}/etc/rc.local" 2>/dev/null; then
    cat >> "${TARGET_DIR}/etc/rc.local" << 'EOF'

# --- MitraNet Network Enhancements Auto-Start ---
/usr/local/bin/mitranet-web > /var/log/mitranet-api.log 2>&1 &
# Launch Xray if active config exists
if [ -f "/usr/local/etc/xray/config.json" ]; then
    /usr/local/etc/rc.d/xray onestart
fi
EOF
fi

# 9. Inject Nginx API location block
NGINX_CONF="${TARGET_DIR}/usr/local/etc/nginx/nginx.conf"
if [ -f "${NGINX_CONF}" ] && ! grep -q "/api/mitranet" "${NGINX_CONF}"; then
    echo "[*] Adding /api/mitranet location to Nginx configuration..."
    python3 -c "
with open('${NGINX_CONF}', 'r') as f:
    content = f.read()

snippet = '''
\tlocation /api/mitranet {
\t    proxy_pass http://127.0.0.1:8080;
\t    proxy_set_header Host \$host;
\t    proxy_set_header X-Real-IP \$remote_addr;
\t}
'''

if 'location /installer {' in content:
    content = content.replace('location /installer {', snippet + '\n\tlocation /installer {')
    with open('${NGINX_CONF}', 'w') as f:
        f.write(content)
"
fi

echo "[+] MitraNet features successfully injected into ${TARGET_DIR}!"

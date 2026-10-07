# MITRANET WEBUI DEPLOYMENT & SYSTEMD INTEGRATION

## 1. Systemd Service Unit
The WebUI is packaged as a standard systemd service unit located at `/lib/systemd/system/mitranet-webui.service`:

```ini
[Unit]
Description=MitraNet WebUI Management Layer and REST API
After=network.target network-online.target mitranet-firewall.service
Wants=network.target

[Service]
Type=simple
User=root
Group=root
WorkingDirectory=/mitranet
ExecStart=/usr/bin/python3 -m mitranet.src.api.server
Restart=always
RestartSec=3
KillMode=process

# Security Hardening
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ReadWritePaths=/etc/mitranet /etc/wireguard /var/lib/mitranet /var/log /run /tmp
ProtectHome=true

[Install]
WantedBy=multi-user.target
```

## 2. Managing the Service
- **Enable on boot:** `systemctl enable mitranet-webui.service`
- **Start service:** `systemctl start mitranet-webui.service`
- **Check status:** `systemctl status mitranet-webui.service`
- **Restart service:** `systemctl restart mitranet-webui.service`

## 3. Network Access & Ports
- Default port: `8443`
- Host access via VirtualBox port forwarding: `http://127.0.0.1:8443/`

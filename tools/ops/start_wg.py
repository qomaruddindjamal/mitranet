
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER

import paramiko

c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(LAB_HOST, port=22, username=LAB_USER, password=LAB_PASS)
cmd = """
mkdir -p /etc/wireguard
priv=$(wg genkey)
pub=$(echo "$priv" | wg pubkey)
echo "$priv" > /etc/wireguard/server_private.key
echo "$pub" > /etc/wireguard/server_public.key
chmod 600 /etc/wireguard/server_private.key

cat > /etc/wireguard/wg0.conf << EOF
[Interface]
Address = 10.10.99.1/24
ListenPort = 51820
PrivateKey = $priv

[Peer]
# Peer: Smartphone HP Direct
PublicKey = ho3tpxfK5gnXSO9PZ5RJvn4YAVjmUEkQ88888888888=
AllowedIPs = 10.10.99.10/32
EOF

systemctl enable --now wg-quick@wg0
sleep 1
systemctl is-active wg-quick@wg0
wg show
"""
stdin, stdout, stderr = c.exec_command(cmd)
print('STDOUT:\n' + stdout.read().decode('utf-8', errors='ignore'))
print('STDERR:\n' + stderr.read().decode('utf-8', errors='ignore'))
c.close()

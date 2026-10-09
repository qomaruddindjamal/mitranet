import paramiko

c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect('192.168.56.101', port=22, username='root', password='mitranet')
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

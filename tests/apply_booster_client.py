import paramiko
import json

MINIPC_IP = "10.10.66.228"
MINIPC_USER = "root"
MINIPC_PASS = "mitranet"

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(MINIPC_IP, username=MINIPC_USER, password=MINIPC_PASS, timeout=8)

# 1. Update booster_config.json preserving the actual client private keys that match MikroTik VPS peers
# On MikroTik:
# Peer 11 (interface wg-boost1): public-key="ajiHxAhDgW0rSsadssTNtwq51u1X5dHnMmaTUGPfA1o="
# Peer 12 (interface wg-boost2): public-key="jtnP093wZuCW7jwYWJEfdqOkTq2NVl9IVdqNpiZLRQc="
#
# On Mini PC:
# wgboost1 has private-key: uHqz5T3CEDas8edg8Dm919+Dsdk4ych7JYPEGE/amUg= (public key = ajiHxAhDgW0rSsadssTNtwq51u1X5dHnMmaTUGPfA1o=)
# wgboost2 has private-key: GJRoPExo3z7ixI6S8dRj0JSix3CjIa189fjBVw3QaUE= (public key = jtnP093wZuCW7jwYWJEfdqOkTq2NVl9IVdqNpiZLRQc=)

stream_keys = {
    "1": {
        "privkey": "uHqz5T3CEDas8edg8Dm919+Dsdk4ych7JYPEGE/amUg=",
        "pubkey": "ajiHxAhDgW0rSsadssTNtwq51u1X5dHnMmaTUGPfA1o="
    },
    "2": {
        "privkey": "GJRoPExo3z7ixI6S8dRj0JSix3CjIa189fjBVw3QaUE=",
        "pubkey": "jtnP093wZuCW7jwYWJEfdqOkTq2NVl9IVdqNpiZLRQc="
    }
}

payload = {
    "role": "client",
    "enabled": True,
    "vps_host": "103.93.162.168",
    "stream_count": 2,
    "client_stream_count": 2,
    "server_stream_count": 2,
    "tunnel_type": "wireguard",
    "balancer_mode": "ecmp",
    "dscp_mode": "AF41",
    "clamp_mss": 1360,
    "enable_bbr": True,
    "peer_public_key": "V8T7f3Ec13e1imlbU/bfH4q/IEkcoM3YtZtIkSyO6y8=",
    "stream_keys": stream_keys,
    "server_enabled": False,
    "server_listen_port_start": 51831,
    "server_subnet": "10.250.0.0/16",
    "server_public_key": "bED4AoWOTeHcxcSuXGvMCNa/Nv8I1TaG3mZokjhRfD8=",
    "server_peers": [],
    "streams": []
}

sftp = ssh.open_sftp()
with sftp.open("/etc/mitranet/secrets/booster_config.json", "w") as f:
    f.write(json.dumps(payload, indent=2))
sftp.close()

# 2. Make sure wgboost1 and wgboost2 wireguard configs and interfaces are correctly UP
cmds = [
    "ip link set wgboost1 up 2>/dev/null",
    "ip link set wgboost2 up 2>/dev/null",
    "ip route replace default scope global nexthop dev wgboost1 weight 1 nexthop dev wgboost2 weight 1 2>/dev/null || true",
    "ip route replace default scope global table 51820 nexthop dev wgboost1 weight 1 nexthop dev wgboost2 weight 1 2>/dev/null || true",
    "sysctl -w net.ipv4.fib_multipath_hash_policy=1 2>/dev/null",
    "sysctl -w net.ipv4.tcp_congestion_control=bbr 2>/dev/null",
    "sysctl -w net.core.default_qdisc=fq 2>/dev/null",
    "iptables -t nat -C POSTROUTING -o wgboost1 -j MASQUERADE 2>/dev/null || iptables -t nat -A POSTROUTING -o wgboost1 -j MASQUERADE",
    "iptables -t nat -C POSTROUTING -o wgboost2 -j MASQUERADE 2>/dev/null || iptables -t nat -A POSTROUTING -o wgboost2 -j MASQUERADE",
    "iptables -t mangle -C POSTROUTING -o wgboost1 -j DSCP --set-dscp 0x28 2>/dev/null || iptables -t mangle -A POSTROUTING -o wgboost1 -j DSCP --set-dscp 0x28",
    "iptables -t mangle -C POSTROUTING -o wgboost2 -j DSCP --set-dscp 0x28 2>/dev/null || iptables -t mangle -A POSTROUTING -o wgboost2 -j DSCP --set-dscp 0x28",
    "iptables -t mangle -C POSTROUTING -o wgboost1 -p tcp --tcp-flags SYN,RST SYN -j TCPMSS --set-mss 1360 2>/dev/null || iptables -t mangle -A POSTROUTING -o wgboost1 -p tcp --tcp-flags SYN,RST SYN -j TCPMSS --set-mss 1360",
    "iptables -t mangle -C POSTROUTING -o wgboost2 -p tcp --tcp-flags SYN,RST SYN -j TCPMSS --set-mss 1360 2>/dev/null || iptables -t mangle -A POSTROUTING -o wgboost2 -p tcp --tcp-flags SYN,RST SYN -j TCPMSS --set-mss 1360"
]

for cmd in cmds:
    ssh.exec_command(cmd)

stdin, stdout, stderr = ssh.exec_command("wg show")
print("=== WG SHOW ON MINIPC ===")
print(stdout.read().decode())

ssh.close()
print("APPLIED BOOSTER CLIENT SUCCESSFULLY!")

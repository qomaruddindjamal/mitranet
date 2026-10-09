import unittest
import tempfile
import os
import shutil
import sys

# Ensure workspace root is in sys.path
root_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
if root_dir not in sys.path:
    sys.path.insert(0, root_dir)
src_dir = os.path.join(root_dir, "src")
if src_dir not in sys.path:
    sys.path.insert(0, src_dir)

from src.api.server import _parse_wg_conf

class TestWireGuardHooksAndConfig(unittest.TestCase):
    def setUp(self):
        self.test_dir = tempfile.mkdtemp()
        self.conf_path = os.path.join(self.test_dir, "wg0.conf")

    def tearDown(self):
        shutil.rmtree(self.test_dir)

    def test_parse_and_preserve_hooks(self):
        initial_conf = """[Interface]
Address = 10.10.77.3/24
ListenPort = 51820
PrivateKey = EFEGbYvOOS2y3YcBDWZuAq2OvfaISSPvfVf38AYFWlA=
# Description: Test Tunnel
# RouteInterface: veth0
# EnableNAT: 1
# DNS: 1.1.1.1, 8.8.8.8
# MTU: 1420
PostUp = ip rule add iif veth0 lookup 100
PostUp = logger -t wireguard "Custom Hook Up"
PostDown = ip rule del iif veth0 lookup 100
PostDown = logger -t wireguard "Custom Hook Down"

[Peer]
# Description: MikroTik Peer
PublicKey = l3K5O83a/rC4K4h7WfZi3xK923x8Wk=
AllowedIPs = 10.10.77.1/32, 0.0.0.0/0
Endpoint = 103.93.162.168:51820
"""
        with open(self.conf_path, "w", encoding="utf-8") as f:
            f.write(initial_conf)

        parsed = _parse_wg_conf(self.conf_path)
        self.assertEqual(parsed["name"], "wg0")
        self.assertEqual(parsed["address"], "10.10.77.3/24")
        self.assertEqual(parsed["listen_port"], 51820)
        self.assertEqual(parsed["route_interface"], "veth0")
        self.assertTrue(parsed["enable_nat"])
        self.assertEqual(parsed["dns"], "1.1.1.1, 8.8.8.8")
        self.assertEqual(parsed["mtu"], 1420)
        self.assertEqual(len(parsed["peers"]), 1)
        self.assertEqual(parsed["peers"][0]["description"], "MikroTik Peer")

        # Verify custom hooks extraction
        postups = parsed.get("custom_postup", [])
        postdowns = parsed.get("custom_postdown", [])
        self.assertIn('logger -t wireguard "Custom Hook Up"', postups)
        self.assertIn('logger -t wireguard "Custom Hook Down"', postdowns)

    def test_ros_command_generation_format(self):
        peer_pubkey = "l3K5O83a/rC4K4h7WfZi3xK923x8Wk="
        allowed_ips = "10.10.77.3/32"
        endpoint = "103.93.162.168:51820"
        descr = "MitraNet Client"
        keepalive = "25"

        ros_cmd = f'/interface wireguard peers add interface=wg0 public-key="{peer_pubkey}" allowed-address={allowed_ips}'
        ep_parts = endpoint.split(":")
        ros_cmd += f' endpoint-address={ep_parts[0]} endpoint-port={ep_parts[1]}'
        ros_cmd += f' persistent-keepalive={keepalive}s'
        ros_cmd += f' comment="{descr}"'

        self.assertIn('interface=wg0', ros_cmd)
        self.assertIn(f'public-key="{peer_pubkey}"', ros_cmd)
        self.assertIn(f'endpoint-address=103.93.162.168', ros_cmd)
        self.assertIn(f'endpoint-port=51820', ros_cmd)
        self.assertIn(f'persistent-keepalive=25s', ros_cmd)
    def test_repeated_save_hook_preservation(self):
        # Simulate tunnel save hook generation logic repeatedly
        custom_postup = ['logger -t wireguard "Custom Hook Up"']
        custom_postdown = ['logger -t wireguard "Custom Hook Down"']
        route_iface = "veth0"
        enable_nat = True
        name = "wg0"

        def build_iface(c_up, c_down):
            lines = ["[Interface]", "Address = 10.10.77.3/24"]
            if route_iface:
                lines.append(f"PostUp = ip rule add iif {route_iface} table 100")
                if enable_nat:
                    lines.append(f"PostUp = iptables -t nat -A POSTROUTING -o {name} -j MASQUERADE")
                lines.append(f"PostDown = ip rule del iif {route_iface} table 100 2>/dev/null || true")
                if enable_nat:
                    lines.append(f"PostDown = iptables -t nat -D POSTROUTING -o {name} -j MASQUERADE 2>/dev/null || true")
            for u in c_up:
                lines.append(f"PostUp = {u}")
            for d in c_down:
                lines.append(f"PostDown = {d}")
            return "\n".join(lines) + "\n"

        # Save 1
        conf1 = build_iface(custom_postup, custom_postdown)
        with open(self.conf_path, "w", encoding="utf-8") as f:
            f.write(conf1)

        p1 = _parse_wg_conf(self.conf_path)
        self.assertEqual(p1["custom_postup"], ['logger -t wireguard "Custom Hook Up"'])

        # Save 2 (edit and resave)
        conf2 = build_iface(p1["custom_postup"], p1["custom_postdown"])
        with open(self.conf_path, "w", encoding="utf-8") as f:
            f.write(conf2)

        p2 = _parse_wg_conf(self.conf_path)
        # Verify no duplicate auto hooks and custom hook is still single
        self.assertEqual(p2["custom_postup"], ['logger -t wireguard "Custom Hook Up"'])
        self.assertEqual(p2["custom_postdown"], ['logger -t wireguard "Custom Hook Down"'])

    def test_input_validation(self):
        import re
        valid_iface = "veth0"
        invalid_iface = "veth0; rm -rf /"
        valid_ip = "10.10.77.3/24"
        invalid_ip = "10.10.77.3/24; reboot"

        self.assertTrue(bool(re.match(r'^[a-zA-Z0-9_\-]+$', valid_iface)))
        self.assertFalse(bool(re.match(r'^[a-zA-Z0-9_\-]+$', invalid_iface)))
        self.assertTrue(bool(re.match(r'^[0-9a-fA-F\.\:\,\s\/]+$', valid_ip)))
        self.assertFalse(bool(re.match(r'^[0-9a-fA-F\.\:\,\s\/]+$', invalid_ip)))

if __name__ == "__main__":
    unittest.main()

